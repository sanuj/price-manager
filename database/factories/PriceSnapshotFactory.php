<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\PriceSnapshot::class, function (Faker\Generator $faker) {
    return [
        'selling_price' => $faker->numberBetween(),
        'cost_price' => $faker->numberBetween(),
        'min_price' => $faker->numberBetween(),
        'max_price' => $faker->numberBetween(),

        'marketplace_selling_price' => $faker->numberBetween(),
        'marketplace_cost_price' => $faker->numberBetween(),
        'marketplace_min_price' => $faker->numberBetween(),
        'marketplace_max_price' => $faker->numberBetween(),

        'marketplace_listing_id' => function () {
            return factory(App\MarketplaceListing::class)->create()->getKey();
        },
    ];
});
