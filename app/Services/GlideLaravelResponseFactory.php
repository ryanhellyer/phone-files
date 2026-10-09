<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Response;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;

class GlideLaravelResponseFactory implements ResponseFactoryInterface
{
    public function create(FilesystemOperator $cache, string $path): Response
    {
        $stream = $cache->readStream($path);
        $content = stream_get_contents($stream);
        fclose($stream);

        $headers = [
            'Content-Type' => $cache->mimeType($path),
            'Content-Length' => $cache->fileSize($path),
        ];

        if ($content !== '') {
            $headers['Cache-Control'] = 'max-age=31536000, public';
            $headers['Expires'] = date_create('+1 years')->format('D, d M Y H:i:s').' GMT';
        } else {
            $headers['Cache-Control'] = 'no-store';
        }

        return new Response($content, 200, $headers);
    }
}
