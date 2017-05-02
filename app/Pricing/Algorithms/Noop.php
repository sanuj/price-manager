<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\MarketplaceListing;

class Noop implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
    {
        return $listing->marketplace_selling_price;
    }
}
