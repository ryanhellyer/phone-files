<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        return response()->json($listing);
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
     */
    public function trash()
    {
        $trash = rtrim((string) config('phone.trash_path'), '/');
        $items = [];

        if (is_dir($trash)) {
            foreach (scandir($trash) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $full = $trash.DIRECTORY_SEPARATOR.$name;
                $isDir = is_dir($full);
                $size = is_file($full) ? (int) filesize($full) : 0;

                $items[] = [
                    'name' => $name,
                    'type' => $isDir ? 'dir' : 'file',
                    'size' => $size,
                    'size_label' => $isDir ? '' : $this->humanSize($size),
                ];
            }

            usort($items, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        }

        return view('trash', [
            'items' => $items,
            'trashPath' => $trash,
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

            if (is_dir($full)) {
                $entries[] = [
                    'type' => 'dir',
                    'name' => $name,
                    'path' => $rel,
                    'count' => $this->countChildren($full),
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

        if (! is_dir($trash) && ! mkdir($trash, 0775, true) && ! is_dir($trash)) {
            return response()->json(['ok' => false, 'error' => 'Cannot create trash directory.'], 500);
        }

        $moved = 0;

        foreach ($targets as $relative) {
            $absolute = $this->resolve($relative);

            if ($absolute === null || $absolute === $current) {
                continue;
            }

            $destination = $this->uniqueDestination($trash, basename($absolute));

            if (@rename($absolute, $destination)) {
                $moved++;
            }
        }

        return response()->json(['ok' => true, 'moved' => $moved]);
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

    private function uniqueDestination(string $trash, string $name): string
    {
        $destination = $trash.'/'.$name;

        if (! file_exists($destination)) {
            return $destination;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.'.$extension;

        return $trash.'/'.$base.'-'.time().'-'.bin2hex(random_bytes(3)).$suffix;
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
