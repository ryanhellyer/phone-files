<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileBrowserController extends Controller
{
    /**
     * Image extensions that Glide can turn into thumbnails.
     */
    private const IMAGE_EXTENSIONS = ['avif', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * Square thumbnail widths (px) offered to the browser via srcset.
     */
    private const THUMBNAIL_SIZES = [96, 192, 288, 384];

    /**
     * Render the file browser shell. The client fetches the listing from list().
     */
    public function index(Request $request)
    {
        return view('files', [
            'path' => $this->normalise((string) $request->query('path', '')),
        ]);
    }

    /**
     * JSON listing of the directory in ?path=... (for client-side fetches).
     */
    public function list(Request $request)
    {
        $listing = $this->listing((string) $request->query('path', ''));

        if ($listing === null) {
            abort(404);
        }

        return response()->json($listing, 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Serve a file from the files directory. Dimensioned filenames
     * (photo-96x96.jpg) are delegated to the Glide-powered ImageController;
     * everything else is streamed as the original file.
     */
    public function show(string $path)
    {
        if (preg_match('/-\d+x\d+$/', pathinfo($path, PATHINFO_FILENAME))) {
            return app(ImageController::class)->show($path);
        }

        $absolute = $this->resolve($path);

        if ($absolute === null || ! is_file($absolute)) {
            abort(404);
        }

        return response()->file($absolute);
    }

    /**
     * Move the selected items to the trash.
     */
    public function destroy(Request $request)
    {
        return $this->trashItems($request, keepOnly: false);
    }

    /**
     * Move everything in the current directory EXCEPT the selected items to the trash.
     */
    public function keepOnly(Request $request)
    {
        return $this->trashItems($request, keepOnly: true);
    }

    /**
     * Show the contents of the trash directory (the "Trash" button target).
     * Lists every trashed file with its original relative path so it can be
     * restored to where it came from.
     */
    public function trash()
    {
        $trash = rtrim((string) config('phone.trash_path'), '/');
        $items = [];

        if (is_dir($trash)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($trash, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $full = $file->getPathname();
                $relative = ltrim(str_replace('\\', '/', substr($full, strlen($trash))), '/');
                $size = (int) $file->getSize();
                $deleted = (int) $file->getCTime();

                $items[] = [
                    'name' => $file->getFilename(),
                    'path' => $relative,
                    'size' => $size,
                    'size_label' => $this->humanSize($size),
                    'deleted_ts' => $deleted,
                    'deleted_at' => date('Y-m-d H:i', $deleted),
                ];
            }

            usort($items, fn (array $a, array $b): int => strnatcasecmp($a['path'], $b['path']));
        }

        return view('trash', [
            'items' => $items,
            'trashPath' => $trash,
        ]);
    }

    /**
     * Restore a trashed item to its original location.
     */
    public function restore(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $relative = $this->normalise($data['path']);

        if ($relative === '') {
            abort(422, 'No path supplied.');
        }

        $trash = rtrim((string) config('phone.trash_path'), '/');
        $trashRoot = realpath($trash);
        $filesRoot = realpath((string) config('phone.files_path'));

        if ($trashRoot === false || $filesRoot === false) {
            return response()->json(['ok' => false, 'error' => 'Trash or files directory is unavailable.'], 500);
        }

        $source = realpath($trashRoot.DIRECTORY_SEPARATOR.$relative);

        if ($source === false || ($source !== $trashRoot && ! str_starts_with($source, $trashRoot.DIRECTORY_SEPARATOR))) {
            return response()->json(['ok' => false, 'error' => 'That item is not in the trash.'], 404);
        }

        $destination = $filesRoot.DIRECTORY_SEPARATOR.$relative;
        $parent = dirname($destination);

        if (! is_dir($parent)) {
            @mkdir($parent, 0775, true);
        }

        if (! is_dir($parent) || ! is_writable($parent)) {
            Log::error('Restore target directory is not writable', ['parent' => $parent]);

            return response()->json(['ok' => false, 'error' => 'Could not write to the original location.'], 500);
        }

        // Never overwrite something that is already there.
        if (file_exists($destination)) {
            $destination = $this->uniqueFilePath($parent, basename($destination));
        }

        if (! @rename($source, $destination)) {
            Log::error('Restore move failed', ['source' => $source, 'destination' => $destination]);

            return response()->json(['ok' => false, 'error' => 'Could not restore the item.'], 500);
        }

        $this->pruneEmptyDirectories($trashRoot, dirname($source));

        return response()->json([
            'ok' => true,
            'path' => $relative,
            'restored_to' => ltrim(str_replace('\\', '/', substr($destination, strlen($filesRoot))), '/'),
        ]);
    }

    /**
     * Build the data needed to render a directory.
     *
     * @return array{path: string, parent: ?string, breadcrumbs: array<int, array{name: string, path: string}>, entries: array<int, array<string, mixed>>}|null
     */
    private function listing(string $path): ?array
    {
        $relative = $this->normalise($path);
        $absolute = $this->resolve($relative);

        if ($absolute === null || ! is_dir($absolute)) {
            return null;
        }

        return [
            'path' => $relative,
            'parent' => $relative === '' ? null : (dirname($relative) === '.' ? '' : dirname($relative)),
            'breadcrumbs' => $this->breadcrumbs($relative),
            'entries' => $this->scan($absolute, $relative),
        ];
    }

    /**
     * Read a directory into a sorted list of entries.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scan(string $absolute, string $relative): array
    {
        $entries = [];

        foreach (scandir($absolute) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.')) {
                continue;
            }

            $full = $absolute.DIRECTORY_SEPARATOR.$name;
            $rel = $relative === '' ? $name : $relative.'/'.$name;
            $modified = @filemtime($full) ?: 0;

            if (is_dir($full)) {
                $entries[] = [
                    'type' => 'dir',
                    'name' => $name,
                    'path' => $rel,
                    'count' => $this->countChildren($full),
                    'modified_ts' => $modified,
                ];

                continue;
            }

            $size = is_file($full) ? (int) filesize($full) : 0;
            $isImage = in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
            $url = '/files/'.$this->encodePath($rel);

            $entries[] = [
                'type' => 'file',
                'name' => $name,
                'path' => $rel,
                'size' => $size,
                'size_label' => $this->humanSize($size),
                'is_image' => $isImage,
                'url' => $url,
                'thumbnail' => $isImage ? thumbnail_url($url, self::THUMBNAIL_SIZES[0], self::THUMBNAIL_SIZES[0]) : null,
                'srcset' => $isImage ? $this->thumbnailSrcset($url) : null,
                'modified_ts' => $modified,
            ];
        }

        usort($entries, function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strnatcasecmp($a['name'], $b['name']);
        });

        return $entries;
    }

    /**
     * Move items into the trash directory.
     */
    private function trashItems(Request $request, bool $keepOnly)
    {
        Log::info('Trash operation requested', [
            'keep_only' => $keepOnly,
            'path' => $request->input('path'),
            'paths' => $request->input('paths'),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent(),
        ]);

        $data = $request->validate([
            'path' => ['nullable', 'string'],
            'paths' => ['array'],
            'paths.*' => ['string'],
        ]);

        $currentRelative = $this->normalise((string) ($data['path'] ?? ''));
        $current = $this->resolve($currentRelative);

        if ($current === null || ! is_dir($current)) {
            abort(404);
        }

        $selected = array_values(array_filter(array_map(
            fn ($p) => $this->normalise((string) $p),
            $data['paths'] ?? []
        )));

        $targets = [];

        if ($keepOnly) {
            foreach ($this->scan($current, $currentRelative) as $entry) {
                if (! in_array($entry['path'], $selected, true)) {
                    $targets[] = $entry['path'];
                }
            }
        } else {
            $targets = $selected;
        }

        $trash = rtrim((string) config('phone.trash_path'), '/');

        if (! is_dir($trash)) {
            @mkdir($trash, 0775, true);
        }

        if (! is_dir($trash) || ! is_writable($trash)) {
            Log::error('Trash directory is not usable', [
                'trash' => $trash,
                'is_dir' => is_dir($trash),
                'is_writable' => is_dir($trash) && is_writable($trash),
            ]);

            return response()->json([
                'ok' => false,
                'moved' => 0,
                'error' => 'The trash directory is not writable: '.$trash,
            ], 500);
        }

        $expected = count($targets);
        $moved = 0;
        $failed = [];

        foreach ($targets as $relative) {
            $absolute = $this->resolve($relative);

            if ($absolute === null || $absolute === $current) {
                $failed[] = $relative;

                continue;
            }

            $destination = $this->trashDestination($trash, $relative);

            if (@rename($absolute, $destination)) {
                $moved++;
            } else {
                $failed[] = $relative;
            }
        }

        if ($moved === 0 && $expected > 0) {
            Log::error('Trash move failed for every item', [
                'trash' => $trash,
                'current' => $currentRelative,
                'failed' => $failed,
            ]);

            return response()->json([
                'ok' => false,
                'moved' => 0,
                'expected' => $expected,
                'failed' => $failed,
                'error' => 'Could not move any items to the trash (check permissions on the trash directory).',
            ], 500);
        }

        if ($failed !== []) {
            Log::warning('Trash move partially failed', [
                'trash' => $trash,
                'current' => $currentRelative,
                'failed' => $failed,
            ]);
        }

        return response()->json([
            'ok' => true,
            'moved' => $moved,
            'expected' => $expected,
            'failed' => $failed,
        ]);
    }

    /**
     * Turn a requested path into a relative, traversal-free path.
     */
    private function normalise(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');

        if ($path === '' || $path === '.') {
            return '';
        }

        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                array_pop($parts);

                continue;
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    /**
     * Resolve a relative path to a real absolute path inside the files root,
     * or null if it is missing or escapes the root.
     */
    private function resolve(string $relative): ?string
    {
        $root = realpath((string) config('phone.files_path'));

        if ($root === false) {
            return null;
        }

        if ($relative === '') {
            return $root;
        }

        // Never expose hidden files/folders (e.g. the trash or sync metadata).
        foreach (explode('/', $relative) as $segment) {
            if (str_starts_with($segment, '.')) {
                return null;
            }
        }

        $candidate = realpath($root.DIRECTORY_SEPARATOR.$relative);

        if ($candidate === false) {
            return null;
        }

        if ($candidate !== $root && ! str_starts_with($candidate, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $candidate;
    }

    /**
     * @return array<int, array{name: string, path: string}>
     */
    private function breadcrumbs(string $relative): array
    {
        $crumbs = [['name' => 'Files', 'path' => '']];

        if ($relative === '') {
            return $crumbs;
        }

        $accumulated = '';

        foreach (explode('/', $relative) as $segment) {
            $accumulated = $accumulated === '' ? $segment : $accumulated.'/'.$segment;
            $crumbs[] = ['name' => $segment, 'path' => $accumulated];
        }

        return $crumbs;
    }

    private function countChildren(string $directory): int
    {
        $count = 0;

        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.')) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Build the trash destination for a file, preserving its relative path.
     */
    private function trashDestination(string $trash, string $relative): string
    {
        $relative = $this->normalise($relative);
        $destination = $trash.'/'.$relative;
        $parent = dirname($destination);

        if (! is_dir($parent)) {
            @mkdir($parent, 0775, true);
        }

        if (file_exists($destination)) {
            $destination = $this->uniqueFilePath($parent, basename($destination));
        }

        return $destination;
    }

    /**
     * Return a non-colliding file path inside $directory.
     */
    private function uniqueFilePath(string $directory, string $name): string
    {
        $candidate = rtrim($directory, '/').'/'.$name;

        if (! file_exists($candidate)) {
            return $candidate;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.'.$extension;

        return rtrim($directory, '/').'/'.$base.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).$suffix;
    }

    /**
     * Remove now-empty directories left behind in the trash after a restore.
     */
    private function pruneEmptyDirectories(string $root, string $start): void
    {
        $root = rtrim($root, '/');
        $directory = $start;

        while ($directory !== $root && str_starts_with($directory, $root.DIRECTORY_SEPARATOR)) {
            $entries = array_diff(scandir($directory) ?: [], ['.', '..']);

            if ($entries !== []) {
                return;
            }

            @rmdir($directory);
            $directory = dirname($directory);
        }
    }

    private function encodePath(string $relative): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $relative)));
    }

    /**
     * Build a Glide-backed srcset string for an image URL.
     */
    private function thumbnailSrcset(string $url): string
    {
        $candidates = [];

        foreach (self::THUMBNAIL_SIZES as $size) {
            $candidates[] = thumbnail_url($url, $size, $size).' '.$size.'w';
        }

        return implode(', ', $candidates);
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return $unit === 0
            ? $bytes.' B'
            : round($value, $value >= 10 ? 0 : 1).' '.$units[$unit];
    }
}
