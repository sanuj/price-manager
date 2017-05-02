<?php namespace App\Contracts;

use App\MarketplaceListing;

interface PricingAlgorithmContract
{
    public function predict(MarketplaceListing $listing): float;
}
