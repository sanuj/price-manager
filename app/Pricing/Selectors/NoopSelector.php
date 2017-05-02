<?php

namespace App\Pricing\Selectors;

use App\Contracts\PricingAlgorithmContract;
use App\Contracts\PricingAlgorithmSelectorContract;
use App\MarketplaceListing;
use App\Pricing\Algorithms\Noop;

class NoopSelector implements PricingAlgorithmSelectorContract
{

    public function algorithm(MarketplaceListing $listing): PricingAlgorithmContract
    {
        return new Noop();
    }
}
