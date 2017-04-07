<?php

namespace App\Jobs;

use App\Company;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use App\MarketplaceListing;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Reprice;

class RepricingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Number of minutes after which listing is repriced.
     *
     * @var int
     */
    protected $frequency = 15;

    /**
     * Bundle multiple reprice requests.
     *
     * @var int
     */
    protected $perRequestCount = 20;

    /**
     * @var \App\Company
     */
    protected $company;

    /**
     * @var \App\Marketplace
     */
    protected $marketplace;
    /**
     * @var \App\Managers\MarketplaceManager
     */
    protected $manager;

    /**
     * Create a new job instance.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     * @param \App\Managers\MarketplaceManager $manager
     */
    public function __construct(Company $company, Marketplace $marketplace, MarketplaceManager $manager)
    {
        $this->company = $company;
        $this->marketplace = $marketplace;
        $this->manager = $manager;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $listings = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                      ->whereCompanyId($this->company->getKey())
                                      ->where('updated_at', '<', Carbon::now()->addMinutes(-$this->frequency))
                                      ->orderBy('updated_at', 'asc')
                                      ->take($this->perRequestCount)
                                      ->get();

        if ($listings->count() === 0) {
            $this->release($this->frequency * 60);  // TODO: It should be in minutes. Confirm this!

            return;
        }

        $credentials = $this->company->credentialsFor($this->marketplace)->credentials;

        $payload = $listings->map(function (MarketplaceListing $listing) {
            $listing = Reprice::reprice($listing);

            return [
                'uid' => $listing->uid,
                'price' => $listing->selling_price,
            ];
        })->toArray();

        $this->manager->driver($this->marketplace->name)
                      ->use($this->marketplace, $credentials)
                      ->setPriceMultiple($payload);

        $this->release(120); // TODO: It should be in minutes. Confirm this!
    }

    /**
     * @return int
     */
    public function getPerRequestCount(): int
    {
        return $this->perRequestCount;
    }

    /**
     * @param int $perRequestCount
     */
    public function setPerRequestCount(int $perRequestCount)
    {
        $this->perRequestCount = $perRequestCount;
    }

    /**
     * @return int
     */
    public function getFrequency(): int
    {
        return $this->frequency;
    }

    /**
     * @param int $frequency
     */
    public function setFrequency(int $frequency)
    {
        $this->frequency = $frequency;
    }
}
