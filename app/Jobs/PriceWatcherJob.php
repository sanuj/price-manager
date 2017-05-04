<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\ThrottleLimitReachedException;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Log;

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

        $this->debug($listings->count().' product listings');

        $api = $this->manager->driver($this->marketplace->name);
        $api->use($this->company->credentialsFor($this->marketplace));

        try {
            $offers = $api->getPrice($listings);
            $competitors = $api->getOffers($listings);

            foreach ($listings as $listing) {
                $this->recordPriceSnapshot($listing, $offers, $competitors);
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
        return MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                 ->whereCompanyId($this->company->getKey())
                                 ->where('updated_at', '<', Carbon::now()->addMinutes(-$this->getFrequency()))
                                 ->orderBy('updated_at', 'asc')
                                 ->take($this->getPerRequestCount())
                                 ->get();
    }

    protected function noListingsLeft()
    {
        $listing = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                     ->whereCompanyId($this->company->getKey())
                                     ->orderBy('updated_at', 'asc')->first();

        if ($listing) {
            $sinceLast = abs(Carbon::now()->diffInMinutes($listing->updated_at));
            $minutes = min($this->getFrequency(), max(0, $this->getFrequency() - $sinceLast));
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
        $snapshot = new Snapshot([
            'repricer_listing_id' => $listing->getKey(),
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

            return;
        }

        if (!$snapshot->save()) {
            Log::error('Failed to store listing in mongodb.', $snapshot->toArray());
            $this->debug('Failed to store listing in mongodb.');
        } else {
            $listing->touch();
        }
    }
}
