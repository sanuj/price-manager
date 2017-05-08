<?php

namespace App\Pricing\Selectors;

use App\Contracts\PricingAlgorithmContract;
use App\Contracts\PricingAlgorithmSelectorContract;
use App\Exceptions\InvalidAlgorithmSelectorException;
use App\MarketplaceListing;

class MarketplaceListingPricingAlgorithmSelector implements PricingAlgorithmSelectorContract
{
    protected $algorithms;

    public function __construct()
    {
        $this->algorithms = config('pricing.algorithms');
    }

    public function algorithm(MarketplaceListing $listing): PricingAlgorithmContract
    {
        $index = $listing->repricing_algorithm['algorithm'];

        if(is_null($index))
            throw new InvalidAlgorithmSelectorException($index);

        return resolve($this->algorithms[$index]);
    }
}
