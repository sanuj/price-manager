<?php return [
    'default' => 'amazon-in',

    'connections' => [
        'amazon-in' => [
            'name' => env('AMAZON_INDIA_APP_NAME', 'pm-india'),
            'version' => env('AMAZON_INDIA_APP_VERSION', 1),
            'ItemCondition' => 'New',
            'ServiceURL' => 'https://mws.amazonservices.in/Products/2011-10-01',
            'FeedServiceURL' => 'https://mws.amazonservices.in/',
        ],
    ],
];
