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
        $this->manager = resolve(MarketplaceManager::class);

        $listings = $this->getRequiredListings();

        if ($listings->count() === 0) {
            $this->noListingsLeft();

            return;
        }

        $this->debug('Watching', $listings->pluck('uid', 'id')->toArray());

        $api = $this->manager->driver($this->marketplace->name);
        $api->use($this->company->credentialsFor($this->marketplace));

        try {
            $offers = $api->getPrice($listings);
            $competitors = $api->getOffers($listings);

            foreach ($listings as $listing) {
                $success = $this->recordPriceSnapshot($listing, $offers, $competitors);
                $this->updateMarketplaceListing($listing, $offers, $success);
            }
        } catch (ThrottleLimitReachedException $e) {
            $this->debug('Throttle limit reached.');
            $this->reschedule(60);

            return;
        }

        $this->reschedule();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function getRequiredListings(): \Illuminate\Support\Collection
    {
        $query = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                   ->whereCompanyId($this->company->getKey())
                                   ->whereRaw('(last_price_watch < "'.Carbon::now()->addMinutes(-$this->getFrequency())->toDateTimeString()
                                       .'" or last_price_watch is null)')
                                   ->orderBy('last_price_watch', 'asc');

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
                                     ->orderBy('last_price_watch', 'asc')->first();

        if ($listing) {
            $minutes = min(
                $this->getFrequency(),
                max(0, $this->getFrequency() - $listing->last_price_watch->diffInMinutes())
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
            $this->debug('Missing Snapshot', [
                'listing_id' => $listing->getKey(),
                'uid' => $listing->uid,
            ]);

            return false; // Not loaded from marketplace, should retry.
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
            return false;
        } else {
            $listing->touch();
        }
        return true;
    }

    protected function updateMarketplaceListing(MarketplaceListing $listing, $offers, $snapshot_success)
    {
        if (!isset($offers[$listing->uid]) or is_null($offer = array_first($offers[$listing->uid]))) {
            // Pass
        }
        else if (is_numeric($offer['price']) && !$this->isPriceEqual($listing->marketplace_selling_price, $offer['price'])) {
            $this->debug("Price inconsistency for MarketplaceListing({$listing->getKey()})", [
                'local' => $listing->marketplace_selling_price,
                'remote' => $offer['price'],
                'use' => $offer,
                'available' => $offers[$listing->uid],
            ]);

            $listing->marketplace_selling_price = $offer['price'];
        }

        if($snapshot_success) {
            $listing->last_price_watch = Carbon::now();
        }

        $listing->save();
    }

    protected function isPriceEqual($price_one, $price_two)
    {
        return abs($price_one - $price_two) < 0.01;
    }
}
