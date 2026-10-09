<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GlideService;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function __construct(
        protected GlideService $glide,
    ) {}

    public function show(string $path)
    {
        $parts = pathinfo($path);
        $dirname = $parts['dirname'] === '.' ? '' : $parts['dirname'].'/';
        $filename = $parts['filename'];
        $extension = $parts['extension'] ?? 'avif';

        if (preg_match('/^(.+?)-(\d+)x(\d+)$/', $filename, $matches)) {
            $baseName = $matches[1];
            $width = (int) $matches[2];
            $height = (int) $matches[3];

            $sourcePath = $dirname.$baseName.'.'.$extension;

            $params = [
                'w' => $width,
                'fit' => 'crop',
                'fm' => $extension,
            ];

            if ($height > 0) {
                $params['h'] = $height;
            }

            return $this->serveImage($sourcePath, $params);
        }

        return $this->notFound();
    }

    private function serveImage(string $sourcePath, array $params)
    {
        try {
            $server = $this->glide->server();
            $cache = $server->getCache();
            $cachePath = $server->getCachePath($sourcePath, $params);

            $response = $server->getImageResponse($sourcePath, $params);

            if (! $cache->fileExists($cachePath) || $cache->fileSize($cachePath) <= 0) {
                if ($cache->fileExists($cachePath)) {
                    $cache->delete($cachePath);
                }

                Log::error('Glide produced an empty image', [
                    'sourcePath' => $sourcePath,
                    'cachePath' => $cachePath,
                    'driver' => config('glide.driver'),
                    'params' => $params,
                ]);

                return $this->notFound();
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('Glide image error', [
                'sourcePath' => $sourcePath,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return $this->notFound();
        }
    }

    private function notFound()
    {
        return response()->view('404', [
            'titleTag' => '404 Error — Ryan Hellyer',
            'template' => 'internal error-404',
        ], 404);
    }
}
