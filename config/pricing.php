<?php return [
    'should_update' => false,

    'algorithms' => [
        \App\Pricing\Algorithms\UserDefinedPrice::class,
    ],

    'default_selector' => 'uniform_random',

    'selectors' => [
        'uniform_random' => \App\Pricing\Selectors\RandomPricingAlgorithmSelector::class,
    ],
];
