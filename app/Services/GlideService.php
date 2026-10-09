<?php

declare(strict_types=1);

namespace App\Services;

use League\Glide\Server;
use League\Glide\ServerFactory;

class GlideService
{
    protected Server $server;

    public function __construct()
    {
        $cachePath = config('glide.cache');
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        $this->server = ServerFactory::create([
            'source' => config('glide.source'),
            'cache' => config('glide.cache'),
            'driver' => config('glide.driver', 'gd'),
            'max_image_size' => config('glide.max_image_size'),
            'defaults' => [
                'q' => 50,
            ],
            'response' => new GlideLaravelResponseFactory(),
        ]);

        $this->server->setCacheWithFileExtensions(true);
    }

    public function server(): Server
    {
        return $this->server;
    }
}
