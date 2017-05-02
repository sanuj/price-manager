<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\MarketplaceListing;

class UserDefinedPrice implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
    {
        return $listing->selling_price / 100.0;
    }
}
