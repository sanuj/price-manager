<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Marketplace::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'website' => $faker->url,
        'logo' => $faker->imageUrl(140, 140),
        'group' => $faker->optional()->word,
        'currency' => $faker->currencyCode,
    ];
});
