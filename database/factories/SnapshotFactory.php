<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Mongo\Snapshot::class, function (Faker\Generator $faker) {
    return [
        '_id' => new \MongoDB\BSON\ObjectID(),
        'marketplace_listing_id' => $faker->numberBetween(),
        'uid' => $faker->regexify("[A-Z0-9]{10}"),
        'marketplace' => 'amazon-in',

        'offers' => function () {
            return [
                "is_fulfilled" => true,
                "reviews" => -1,
                "rating" => -1,
                "price" => 819.0,
                "currency" => "INR",
                "has_buy_box" => true
            ];
        },

        'competitors' => function () {
            return [
                "is_fulfilled" => true,
                "reviews" => -1,
                "rating" => -1,
                "price" => 819.0,
                "currency" => "INR",
                "has_buy_box" => false
            ];
        },

        'timestamp' => \Carbon\Carbon::now(),
    ];
});
