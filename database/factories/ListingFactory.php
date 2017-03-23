<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Listing::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'company_id' => function () {
            return factory(App\Company::class)->create()->getKey();
        },
    ];
});
