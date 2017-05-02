<?php

namespace App\Services;

use App\MarketplaceListing;

class RepriceService
{
    public function reprice(MarketplaceListing $listing): MarketplaceListing
    {
        $price = $this->getSelector($listing)
                      ->algorithm($listing)
                      ->predict($listing);

        $diff = round(abs($listing->marketplace_selling_price - $price), 2);

        if ($diff > .99) {
            // TODO: Update price.
        }



        return $listing;
    }

    /**
     * @param \App\MarketplaceListing $listing
     *
     * @return \App\Contracts\PricingAlgorithmSelectorContract
     */
    protected function getSelector(MarketplaceListing $listing)
    {
        $selector = $listing->algorithm ?? $this->getDefaultSelectorName();

        return resolve(config('pricing.selectors.'.$selector));
    }

    protected function getDefaultSelectorName()
    {
        return config('pricing.default_selector');
    }
}
