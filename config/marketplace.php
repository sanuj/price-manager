<?php return [
    'default' => 'amazon-in',

    'connections' => [
        'amazon-in' => [
            'key' => env('AMAZON_INDIA_KEY'),
            'secret' => env('AMAZON_INDIA_SECRET'),
            'name' => env('AMAZON_INDIA_APP_NAME', 'pm-india'),
            'version' => env('AMAZON_INDIA_APP_VERSION', 1),
        ],
    ],
];
