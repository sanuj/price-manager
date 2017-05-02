<?php

namespace App\Services;

use App\Managers\MarketplaceManager;
use App\MarketplaceListing;
use App\Mongo\PriceHistory;
use Illuminate\Support\Collection;

class RepriceService
{
    /**
     * @var \App\Managers\MarketplaceManager
     */
    protected $manager;


    /**
     * RepriceService constructor.
     *
     * @param \App\Managers\MarketplaceManager $manager
     */
    public function __construct(MarketplaceManager $manager)
    {
        $this->manager = $manager;
    }

    public function repriceAll(Collection $listings)
    {
        $listings = $listings->map(function (MarketplaceListing $listing) {
            return $this->reprice($listing);
        })->filter(function (MarketplaceListing $listing) {
            return $listing->isDirty();
        });

        if (!count($listings)) {
            return;
        }

        $this->manager->driver($listings->first()->marketplace->name)
                      ->setPrice($listings);
    }

    protected function reprice(MarketplaceListing $listing): MarketplaceListing
    {
        $selector = $this->getSelector($listing);
        $algorithm = $selector->algorithm($listing);
        $price = $algorithm->predict($listing);

        $diff = round(abs($listing->marketplace_selling_price - $price), 2);

        with(new PriceHistory([
            'marketplace_listing_id' => $listing->getKey(),
            'algorithm' => get_class($algorithm),
            'selector' => get_class($selector),
            'old_price' => $listing->marketplace_selling_price,
            'price' => $price,
        ]))->save();

        if (config('pricing.should_update') and $diff > .99) {
            $listing->marketplace_selling_price = $price;
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
