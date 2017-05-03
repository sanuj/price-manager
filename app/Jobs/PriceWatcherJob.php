<?php

namespace App\Jobs;

use App\Company;
use App\Exceptions\ThrottleLimitReachedException;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Queue;

class PriceWatcherJob extends SelfSchedulingJob
{
    /**
     * @var \App\Company
     */
    public $company;

    /**
     * @var \App\Marketplace
     */
    public $marketplace;
    /**
     * @var \App\Managers\MarketplaceManager
     */
    protected $manager;

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

        $this->debug('Starting repricer for '.$this->company->name);

        $listings = $this->getRequiredListings();

        if ($listings->count() === 0) {
            $this->noListingsLeft();

            return;
        }

        $this->debug($listings->count().' product listings');

        $api = $this->manager->driver($this->marketplace->name);
        $api->use($this->company->credentialsFor($this->marketplace));

        try {
            $payload = $listings->pluck('uid')->toArray();

            $offers = $api->getPrice($payload);
            $competitors = $api->getOffers($payload);

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

    protected function noListingsLeft(): void
    {
        $this->debug('No tasks left. Rescheduling after '.$this->getFrequency().' minutes.');
        $listing = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                     ->whereCompanyId($this->company->getKey())
                                     ->orderBy('updated_at', 'asc')->first();

        if ($listing) {
            $this->reschedule(60 * min(15, abs(Carbon::now()->diffInMinutes($listing->updated_at))));
        } else {
            $this->reschedule(60 * $this->getFrequency());
        }
    }

    /**
     * @param $listing
     * @param $offers
     * @param $competitors
     */
    protected function recordPriceSnapshot(MarketplaceListing $listing, $offers, $competitors): void
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

        if (!$snapshot->save()) {
            Log::error('Failed to store listing in mongodb.', $snapshot->toArray());
            $this->debug('Failed to store listing in mongodb.');
        } else {
            $listing->touch();
        }
    }
}
