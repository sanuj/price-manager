<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\ThrottleLimitReachedException;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use Illuminate\Database\Eloquent\Collection;
use Log;

class PriceUpdaterJob extends SelfSchedulingJob
{
    /**
     * Bundle multiple reprice requests.
     *
     * @var int
     */
    protected $perRequestCount = 2000;

    /**
     * Create a new job instance.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     */
    public function __construct(Company $company, Marketplace $marketplace)
    {
        $this->company = $company;
        $this->marketplace = $marketplace;
        $this->queue = 'exponent-update';
        $this->connection = null;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // NOTICE: MWS SDK has deprecated code.
        $this->manager = resolve(MarketplaceManager::class);

        $this->debug('Starting repricer for '.$this->company->name);

        try {
            MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                              ->whereCompanyId($this->company->getKey())
                              ->chunk($this->getPerRequestCount(), function ($listings) {
                                  $this->updatePrice($listings);
                              });
        } catch (ThrottleLimitReachedException $e) {
            $this->debug('Rescheduling, throttle limit reached.');
            $this->reschedule(60);

            return;
        } catch (\Throwable $e) {
            $this->debug('There is an error. '.$e->getMessage());

            throw $e;
        }

        $this->reschedule(60 * $this->getFrequency());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection|MarketplaceListing[] $listings
     */
    protected function updatePrice(Collection $listings)
    {
        $api = $this->manager->driver($this->marketplace->name);
        $api->use($this->company->credentialsFor($this->marketplace));

        $listings = $listings->map(function (MarketplaceListing $listing) {
            return $this->reprice($listing);
        })->filter(function (MarketplaceListing $listing) {
            return $listing->isDirty();
        });

        if (!count($listings)) {
            return;
        }

        /** @var MarketplaceListing $listing */
        foreach ($listings as $listing) {
            Log::debug("\tUpdate ({$listing->uid}): {$listing->getOriginal('marketplace_selling_price')} -> {$listings->marketplace_selling_price}");
        }

        if (config('pricing.should_update')) {
            $api->setPrice($listings);
        }

    }

    protected function reprice(MarketplaceListing $listing): MarketplaceListing
    {
        $selector = $this->getSelector($listing);
        $algorithm = $selector->algorithm($listing);
        $price = $algorithm->predict($listing);

        with(new PriceHistory([
            'marketplace_listing_id' => $listing->getKey(),
            'algorithm' => get_class($algorithm),
            'selector' => get_class($selector),
            'old_price' => $listing->marketplace_selling_price,
            'price' => $price,
        ]))->save();

        $diff = round(abs($listing->marketplace_selling_price - $price), 2);
        if ($diff > .99) {
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
        $selector = $listing->repricing_algorithm ? $listing->repricing_algorithm['selector'] : $this->getDefaultSelectorName();

        return resolve(config('pricing.selectors.'.$selector));
    }

    protected function getDefaultSelectorName()
    {
        return config('pricing.default_selector');
    }
}
