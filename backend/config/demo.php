<?php

return [
    'enabled' => (bool) env('DEMO_MODE', false),
    'access_hash' => env('DEMO_ACCESS_HASH', ''),
    'users' => [
        'admin' => ['email' => env('DEMO_ADMIN_EMAIL'), 'password' => env('DEMO_ADMIN_PASSWORD')],
        'seller' => ['email' => env('DEMO_SELLER_EMAIL'), 'password' => env('DEMO_SELLER_PASSWORD')],
        'customer' => ['email' => env('DEMO_CUSTOMER_EMAIL'), 'password' => env('DEMO_CUSTOMER_PASSWORD')],
    ],
];
