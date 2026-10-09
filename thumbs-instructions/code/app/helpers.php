<?php

if (!function_exists('thumbnail_url')) {
    function thumbnail_url(string $url, int $width, int $height): string
    {
        return preg_replace('/\.(avif|jpg|jpeg|png|webp)$/i', '-' . $width . 'x' . $height . '.$1', $url);
    }
}
