<?php

namespace App\Pricing\Selectors;

use App\Contracts\PricingAlgorithmContract;
use App\Contracts\PricingAlgorithmSelectorContract;
use App\MarketplaceListing;

class RandomPricingAlgorithmSelector implements PricingAlgorithmSelectorContract
{
    protected $algorithms;

    public function __construct()
    {
        $this->algorithms = config('pricing.algorithms');
    }

    public function algorithm(MarketplaceListing $listing): PricingAlgorithmContract
    {
        $index = random_int(1, count($this->algorithms));

        return resolve($this->algorithms[$index - 1]);
    }
}
