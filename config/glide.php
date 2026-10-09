<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Glide Source Path
    |--------------------------------------------------------------------------
    |
    | Directory where original (source) images are stored. Glide reads from
    | here when generating thumbnails.
    |
    */

    'source' => env('PHONE_FILES_PATH', base_path('files')),

    /*
    |--------------------------------------------------------------------------
    | Glide Cache Path
    |--------------------------------------------------------------------------
    |
    | Directory where Glide stores cached/resized images. Should be writable.
    |
    */

    'cache' => storage_path('app/glide'),

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Which Intervention Image driver to use: 'gd' or 'imagick'.
    | Defaults to GD, which is widely available and reliably writes AVIF.
    |
    | Do NOT auto-detect from extension_loaded('imagick'): an Imagick build
    | without an AVIF encoder (shown as "AVIF r--" in `convert -list format`)
    | silently produces 0-byte images. Only use imagick after confirming it
    | can WRITE AVIF.
    |
    */

    'driver' => env('GLIDE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Max Image Size
    |--------------------------------------------------------------------------
    |
    | Maximum total pixel count (width * height) for any generated image.
    | Prevents resource exhaustion from absurdly large requests.
    |
    */

    'max_image_size' => 2000 * 2000,

];
