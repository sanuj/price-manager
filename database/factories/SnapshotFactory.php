<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Mongo\Snapshot::class, function (Faker\Generator $faker) {
    $has_buy_box = $faker->boolean();
    return [
        '_id' => new \MongoDB\BSON\ObjectID(),
        'marketplace_listing_id' => $faker->numberBetween(),
        'uid' => $faker->regexify("[A-Z0-9]{10}"),
        'marketplace' => 'amazon-in',   

        'offers' => function () use ($faker, $has_buy_box) {
            $offers = [];
            $is_fulfilled = $faker->boolean();
            foreach(range(1, 2) as $i) {
                array_push($offers, [
                    "is_fulfilled" => $i === 1 ? $is_fulfilled : !$is_fulfilled,
                    "reviews" => $faker->numberBetween(1, 1000),
                    "rating" => $faker->numberBetween(1, 5),
                    "price" => $faker->randomFloat(2, 800, 900),
                    "currency" => "INR",
                    "has_buy_box" => $has_buy_box
                ]);
            }
            return $offers;
        },

        'competitors' => function () use ($faker, $has_buy_box) {
            $competitors = [];
            foreach(range(1, $faker->numberBetween(2, 8)) as $i) {
                array_push($competitors, [
                    "is_fulfilled" => $faker->boolean(),
                    "reviews" => $faker->numberBetween(1, 1000),
                    "rating" => $faker->numberBetween(1, 5),
                    "price" => $faker->randomFloat(2, 800, 900),
                    "currency" => "INR",
                    "has_buy_box" => $i === 1 ? !$has_buy_box : false,
                ]);
            }
            return $competitors;
        },

        'timestamp' => \Carbon\Carbon::now(),
    ];
});
