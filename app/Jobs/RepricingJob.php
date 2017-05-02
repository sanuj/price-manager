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

        $listings = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                      ->whereCompanyId($this->company->getKey())
                                      ->where('updated_at', '<', Carbon::now()->addMinutes(-$this->frequency))
                                      ->orderBy('updated_at', 'asc')
                                      ->take($this->perRequestCount)
                                      ->get();

        if ($listings->count() === 0) {
            $this->debug('No tasks left. Rescheduling after '.$this->getFrequency().' minutes.');
            $this->reschedule(60 * $this->getFrequency());

            return;
        }

        $this->debug($listings->count().' product listings');

        try {
            $api = $this->manager->driver($this->marketplace->name);
            $api->use($this->company->credentialsFor($this->marketplace));

            // <--- NOTICE: Watching price changes.
            $payload = $listings->pluck('uid')->toArray();

            $offers = $api->getPrice($payload);
            $competitors = $api->getOffers($payload);

            foreach ($listings as $listing) {
                /** @var MarketplaceListing $listing */

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
        } catch (ThrottleLimitReachedException $e) {
            $this->reschedule(60 * $this->getFrequency());

            // TODO: Get API cool down time from driver.
            return;
        } catch (\Throwable $e) {
            $this->debug('There is an error. '.$e->getMessage());

            throw $e;
        }

        // Watching Price Changes --->
        $this->reschedule();
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

    public function reschedule(int $seconds = 0)
    {
        $job = new RepricingJob($this->company, $this->marketplace);

        // TODO: Push on ~same queue~ again.
        if ($seconds) {
            Queue::connection(config('queue.repricer'))->later($seconds, $job);
        } else {
            Queue::connection(config('queue.repricer'))->push($job);
        }
    }

    protected function debug(string $message, array $payload = [])
    {
        Log::debug('RepricerService::Company('.$this->company->getKey().') - '.$message, $payload);
        echo('RepricerService::Company('.$this->company->getKey().') - '.$message.PHP_EOL);
        if (count($payload)) {
            dump($payload);
        }
    }
}
