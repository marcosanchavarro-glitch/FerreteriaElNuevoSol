<?php

return [
    'storage' => env('CATALOG_STORAGE', 'local'),
    'cloudinary' => [
        'cloud' => env('CLOUDINARY_CLOUD_NAME'),
        'key' => env('CLOUDINARY_API_KEY'),
        'secret' => env('CLOUDINARY_API_SECRET'),
        'prefix' => env('CLOUDINARY_PREFIX', 'el-nuevo-sol-demo'),
    ],
];
