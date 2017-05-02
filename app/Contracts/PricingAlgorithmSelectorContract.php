<?php namespace App\Contracts;

use App\MarketplaceListing;

interface PricingAlgorithmSelectorContract
{
    public function algorithm(MarketplaceListing $listing): PricingAlgorithmContract;
}
