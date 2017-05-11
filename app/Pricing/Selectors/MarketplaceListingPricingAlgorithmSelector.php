<?php

namespace App\Pricing\Selectors;

use App\Contracts\PricingAlgorithmContract;
use App\Contracts\PricingAlgorithmSelectorContract;
use App\MarketplaceListing;

class MarketplaceListingPricingAlgorithmSelector implements PricingAlgorithmSelectorContract
{
    public function algorithm(MarketplaceListing $listing): PricingAlgorithmContract
    {
        return resolve('\App\Pricing\Algorithms\\'.$listing->repricing_algorithm['algorithm']);
    }
}
