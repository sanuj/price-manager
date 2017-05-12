<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\MarketplaceListingException;
use App\Exceptions\ThrottleLimitReachedException;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use App\Mongo\PriceHistory;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Maknz\Slack\Client as Slack;

class PriceUpdaterJob extends SelfSchedulingJob
{
    /**
     * @var string
     */
    public $queue = 'exponent-update';
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
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Throwable
     */
    public function handle()
    {

        $this->manager = resolve(MarketplaceManager::class);

        $this->debug('Running for company '.$this->company->name.'.');

        try {
            MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                              ->whereCompanyId($this->company->getKey())
                              ->where('status', '<>', MarketplaceListing::STATUS_INACTIVE)
                              ->chunk($this->getPerRequestCount(), function ($listings) {
                                  $this->updatePrice($listings);
                              });
        } catch (MarketplaceListingException $e) {
            $this->disableAndReschedule($e);

            return;
        } catch (ThrottleLimitReachedException $e) {
            $this->debug('Rescheduling, throttle limit reached.');
            $this->reschedule(60);

            return;
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
            $this->debug('No listings left to reprice.');

            return;
        }

        /** @var MarketplaceListing $listing */
        foreach ($listings as $listing) {
            $this->debug("\tUpdate ({$listing->uid}): {$listing->getOriginal('marketplace_selling_price')} -> {$listing->marketplace_selling_price}");
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

        $this->debug('Saving snapshot for listing: '.$listing->id.' with predicted price: '.$price);
        with(new PriceHistory([
            'marketplace_listing_id' => $listing->getKey(),
            'algorithm' => get_class($algorithm),
            'selector' => get_class($selector),
            'old_price' => $listing->marketplace_selling_price,
            'price' => $price,
        ]))->save();
        $this->debug('Snapshot saved for listing: '.$listing->id);

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

    /**
     * @param MarketplaceListingException $exception
     */
    protected function disableAndReschedule(MarketplaceListingException $exception)
    {
        $listing = $exception->getMarketplaceListing();
        $listing->update(['status' => 0]);

        resolve(Slack::class)
            ->attach([
                'ASIN' => $listing->uid,
                'SKU' => $listing->companyProduct->sku,
                'reason' => get_class($exception),
                'company' => $this->company->getKey(),
                'marketplace' => $this->marketplace->getKey(),
            ])
            ->send('Disabling listing on '.$this->marketplace->name.' for '.$this->company->name.'.');

        Log::error($exception);

        $this->reschedule();
    }
}
