<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Files Path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the directory holding the phone's synced files. These
    | are NOT web-served; Laravel reads them (and Glide sizes images from them)
    | via this path. Different on every server, so it comes from .env.
    |
    */

    'files_path' => env('PHONE_FILES_PATH', base_path('files')),

    /*
    |--------------------------------------------------------------------------
    | Trash Path
    |--------------------------------------------------------------------------
    |
    | Where deleted items are moved. Defaults to a .trash folder beside the
    | project root. Must be writable by the web user.
    |
    */

    'trash_path' => env('PHONE_TRASH_PATH', base_path('.trash')),

];
