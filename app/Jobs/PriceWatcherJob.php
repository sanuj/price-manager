<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\ThrottleLimitReachedException;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;

class PriceWatcherJob extends SelfSchedulingJob
{
    public $queue = 'exponent-watch';

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
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); // NOTICE: MWS SDK has deprecated code.
        $this->manager = resolve(MarketplaceManager::class);

        $this->debug('Run for '.$this->company->name);

        $listings = $this->getRequiredListings();

        if ($listings->count() === 0) {
            $this->noListingsLeft();

            return;
        }

        $api = $this->manager->driver($this->marketplace->name);
        $api->use($this->company->credentialsFor($this->marketplace));

        try {
            $offers = $api->getPrice($listings);
            $competitors = $api->getOffers($listings);

            foreach ($listings as $listing) {
                $this->recordPriceSnapshot($listing, $offers, $competitors);
                $this->updateMarketplaceListing($listing, $offers);
            }
        } catch (ThrottleLimitReachedException $e) {
            $this->debug('Rescheduling, throttle limit reached.');
            $this->reschedule(60);

            return;
        } catch (\Throwable $e) {
            $this->debug('There is an error. '.$e->getMessage());

            throw $e;
        }

        // Watching Price Changes --->
        $this->reschedule();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getRequiredListings(): \Illuminate\Support\Collection
    {
        $query = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                   ->whereCompanyId($this->company->getKey())
                                   ->where('updated_at', '<', Carbon::now()->addMinutes(-$this->getFrequency()))
                                   ->orderBy('updated_at', 'asc');

        $total = $query->count();
        $listings = $query->take($this->getPerRequestCount())->get();
        $this->debug('Processing '.$listings->count()."/${total} product listings.");

        return $listings;
    }

    protected function noListingsLeft()
    {
        /** @var MarketplaceListing $listing */
        $listing = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                     ->whereCompanyId($this->company->getKey())
                                     ->orderBy('updated_at', 'asc')->first();

        if ($listing) {
            $minutes = min(
                $this->getFrequency(),
                max(0, $this->getFrequency() - $listing->updated_at->diffInMinutes())
            );

            $this->debug('No tasks left. Rescheduling after '.$minutes.' minutes.');
            $this->reschedule(60 * $minutes);
        } else {
            $this->debug('No tasks left. Rescheduling after '.$this->getFrequency().' minutes.');
            $this->reschedule(60 * $this->getFrequency());
        }
    }

    /**
     * @param $listing
     * @param $offers
     * @param $competitors
     */
    protected function recordPriceSnapshot(MarketplaceListing $listing, $offers, $competitors)
    {
        if (!isset($competitors[$listing->uid]) and !isset($offers[$listing->uid])) {
            $this->debug('Missing Snapshot', ['listing_id' => $listing->getKey(), 'uid' => $listing->uid]);

            return; // Not loaded from marketplace, should retry.
        }

        $snapshot = new Snapshot([
            'marketplace_listing_id' => $listing->getKey(),
            'uid' => $listing->uid,
            'marketplace' => $this->marketplace->name,
            'offers' => $offers[$listing->uid] ?? [],
            'competitors' => array_map(function (Marketplace\ProductOffer $offer) {
                return $offer->toArray();
            }, $competitors[$listing->uid] ?? []),
            'timestamp' => Carbon::now(),
        ]);

        if (app()->environment('testing')) {
            $listing->touch();
        } elseif (!$snapshot->save()) {
            $this->debug('Failed to store listing in mongodb.', $snapshot->toArray());
        } else {
            $listing->touch();
        }
    }

    protected function updateMarketplaceListing(MarketplaceListing $listing, $offers) {
        if(count($offers) === 0) return;
        $offer = $offers[0];
        if(is_float($offer->price) && !$this->isPriceEqual($listing->marketplace_selling_price, $offer->price) ) {
            $listing->marketplace_selling_price = $offer->price;
            $listing->save();
        }
    }

    protected function isPriceEqual($price_one, $price_two) {
        return abs($price_one - $price_two) < 0.01;
    }
}
