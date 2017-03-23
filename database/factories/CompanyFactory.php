<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Company::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
    ];
});
