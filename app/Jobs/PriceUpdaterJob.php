<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\MarketplaceListingException;
use App\Exceptions\NoSnapshotsWithOffersException;
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

        /** @var MarketplaceListing[]|Collection $listings */
        $listings = $listings->map(function (MarketplaceListing $listing) {
            return $this->reprice($listing);
        })->filter(function (MarketplaceListing $listing) {
            if($listing->getOriginal('status') === MarketplaceListing::STATUS_ACTIVE and
                $listing->status === MarketplaceListing::STATUS_NO_OFFERS) {
                $listing->save();
            }
            return $listing->status === MarketplaceListing::STATUS_ACTIVE;
        });

        if (!count($listings)) {
            $this->debug('No price updated.', $listings->pluck('marketplace_selling_price', 'id')->toArray());

            return;
        }

        $changes = $listings->map(function (MarketplaceListing $listing) {
            return [
                'id' => $listing->getKey(),
                'old' => $listing->getOriginal('marketplace_selling_price'),
                'new' => $listing->marketplace_selling_price,
            ];
        })->toArray();

        if (config('pricing.should_update')) {
            $api->setPrice($listings);
            $listings->each(function (MarketplaceListing $listing) {
                $listing->save();
            });

            $this->debug('Update price on '.$this->marketplace->name, $changes);
        } else {
            $this->debug('Price updating disabled. Expected changes: ', $changes);
        }
    }

    protected function reprice(MarketplaceListing $listing): MarketplaceListing
    {
        $selector = $this->getSelector($listing);
        $algorithm = $selector->algorithm($listing);
        try {
            $price = round($algorithm->predict($listing));
        }
        catch(NoSnapshotsWithOffersException $e) {
            $this->debug('No offers present for listing id '.$listing->getKey());
            $listing->status = MarketplaceListing::STATUS_NO_OFFERS;
            return $listing;
        }
        $listing->status = MarketplaceListing::STATUS_ACTIVE; // Offers are present.

        with(new PriceHistory([
            'marketplace_listing_id' => $listing->getKey(),
            'algorithm' => get_class($algorithm),
            'selector' => get_class($selector),
            'old_price' => $listing->marketplace_selling_price,
            'price' => $price,
            'should_update' => config('pricing.should_update'),
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

    /**
     * @param MarketplaceListingException $exception
     */
    protected function disableAndReschedule(MarketplaceListingException $exception)
    {
        $listing = $exception->getMarketplaceListing();
        $listing->update(['status' => MarketplaceListing::STATUS_INACTIVE]);

        resolve(Slack::class)
            ->attach([
                'fallback' => 'Reason: '.get_class($exception),
                'text' => 'Reason: '.get_class($exception),
                'color' => 'danger',
                'fields' => [
                    ['title' => 'Marketplace Listing', 'value' => $listing->getKey(), 'short' => true],
                    ['title' => 'ASIN', 'value' => $listing->uid, 'short' => true],
                    ['title' => 'SKU', 'value' => $listing->companyProduct->sku, 'short' => true],
                    ['title' => 'Company', 'value' => $this->company->getKey(), 'short' => true],
                ],
            ])
            ->send('Disabling listing on '.$this->marketplace->name.' for '.$this->company->name.'.');

        Log::error($exception);

        $this->reschedule();
    }
}
