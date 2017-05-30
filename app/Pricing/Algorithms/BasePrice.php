<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\Exceptions\InvalidPriceException;
use App\MarketplaceListing;

abstract class BasePrice implements PricingAlgorithmContract
{
    protected $min_price = 0;
    protected $max_price = 0;
    protected $selling_price = 0;

    protected function init(MarketplaceListing $listing)
    {
        $this->min_price = $listing->marketplace_min_price;
        $this->max_price = $listing->marketplace_max_price;
        $this->selling_price = $listing->marketplace_selling_price;
        $this->validateListing($listing);
    }

    protected function validateListing(MarketplaceListing $listing)
    {
        if ($this->min_price == 0) {
            throw new InvalidPriceException($listing, 'min');
        }

        if ($this->max_price == 0 or $this->max_price < $this->min_price) {
            throw new InvalidPriceException($listing, 'max');
        }

        if($this->selling_price == 0) {
            throw new InvalidPriceException($listing, 'selling');
        }
    }

    protected function postProcessPrice($price) {
        return min($this->max_price, max($price, $this->min_price));
    }

    abstract public function calculatePrice(MarketplaceListing $listing);

    public function predict(MarketplaceListing $listing): float {
        $predicted_price = $this->calculatePrice($listing);
        return $this->postProcessPrice($predicted_price);
    }
}
