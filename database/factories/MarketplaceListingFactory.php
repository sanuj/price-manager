<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\MarketplaceListing::class, function (Faker\Generator $faker) {
    return [
        'uid' => $faker->uuid,
        'sku' => $faker->ean13,
        'url' => $faker->url,
        'ref_num' => $faker->optional()->numberBetween(),

        'selling_price' => $faker->numberBetween(),
        'cost_price' => $faker->numberBetween(),
        'min_price' => $faker->numberBetween(),
        'max_price' => $faker->numberBetween(),

        'marketplace_selling_price' => $faker->numberBetween(),
        'marketplace_cost_price' => $faker->numberBetween(),
        'marketplace_min_price' => $faker->numberBetween(),
        'marketplace_max_price' => $faker->numberBetween(),

        'marketplace_id' => function () {
            return factory(App\Marketplace::class)->create()->getKey();
        },
        'company_id' => function () {
            return factory(App\Company::class)->create()->getKey();
        },
        'company_product_id' => function () {
            return factory(App\CompanyProduct::class)->create()->getKey();
        },
    ];
});
