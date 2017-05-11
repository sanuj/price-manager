<?php return [
    'should_update' => env('SHOULD_UPDATE_PRICE', false),

    'algorithms' => [
        \App\Pricing\Algorithms\UserDefinedPrice::class,
        \App\Pricing\Algorithms\BuyBoxShareHeuristicPrice::class,
    ],

    'default_selector' => 'uniform_random',

    'selectors' => [
        'uniform_random' => \App\Pricing\Selectors\RandomPricingAlgorithmSelector::class,
        'marketplace_listing' => \App\Pricing\Selectors\MarketplaceListingPricingAlgorithmSelector::class
    ],
];
