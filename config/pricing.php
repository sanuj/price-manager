<?php return [
    'should_update' => false,

    'algorithms' => [
        \App\Pricing\Algorithms\UserDefinedPrice::class,
        \App\Pricing\Algorithms\BuyBoxShareHeuristicPrice::class,
    ],

    'default_selector' => 'uniform_random',

    'selectors' => [
        'uniform_random' => \App\Pricing\Selectors\RandomPricingAlgorithmSelector::class,
    ],
];
