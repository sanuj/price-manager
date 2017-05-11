<?php return [
    'should_update' => env('SHOULD_UPDATE_PRICE', false),

    'algorithms' => [
        'UserDefinedPrice',
        'BuyBoxShareHeuristicPrice',
    ],

    'default_selector' => 'uniform_random',

    'selectors' => [
        'uniform_random' => \App\Pricing\Selectors\RandomPricingAlgorithmSelector::class,
        'marketplace_listing' => \App\Pricing\Selectors\MarketplaceListingPricingAlgorithmSelector::class
    ],
];
