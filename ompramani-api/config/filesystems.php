<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Logical Disk Selectors (Local vs Cloudflare R2)
    |--------------------------------------------------------------------------
    |
    | Define which disk handles public assets (images, audio, thumbnails)
    | and which disk handles private assets (source docx/pdf, backups).
    | Default to local storage in development; set to r2_public / r2_private
    | in production once credentials are confirmed.
    |
    */
    'public_disk' => env('FILESYSTEM_PUBLIC_DISK', 'public'),
    'private_disk' => env('FILESYSTEM_PRIVATE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'r2_public' => [
            'driver' => 's3',
            'key' => env('R2_PUBLIC_ACCESS_KEY_ID'),
            'secret' => env('R2_PUBLIC_SECRET_ACCESS_KEY'),
            'region' => env('R2_PUBLIC_DEFAULT_REGION', 'auto'),
            'bucket' => env('R2_PUBLIC_BUCKET', 'ompramani-public'),
            'url' => env('R2_PUBLIC_URL'),
            'endpoint' => env('R2_PUBLIC_ENDPOINT'),
            'use_path_style_endpoint' => env('R2_PUBLIC_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
            'visibility' => 'public',
        ],

        'r2_private' => [
            'driver' => 's3',
            'key' => env('R2_PRIVATE_ACCESS_KEY_ID'),
            'secret' => env('R2_PRIVATE_SECRET_ACCESS_KEY'),
            'region' => env('R2_PRIVATE_DEFAULT_REGION', 'auto'),
            'bucket' => env('R2_PRIVATE_BUCKET', 'ompramani-private'),
            'endpoint' => env('R2_PRIVATE_ENDPOINT'),
            'use_path_style_endpoint' => env('R2_PRIVATE_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
            'visibility' => 'private',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
