<?php

namespace App\Jobs;

use App\Company;
use App\Marketplace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Queue;

abstract class SelfSchedulingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var \App\Marketplace
     */
    public $marketplace;
    /**
     * @var \App\Company
     */
    public $company;
    /**
     * Bundle multiple reprice requests.
     *
     * @var int
     */
    protected $perRequestCount = 20;
    /**
     * Number of minutes after which listing is repriced.
     *
     * @var int
     */
    protected $frequency = 15;
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
        $job = new PriceWatcherJob($this->company, $this->marketplace);

        if ($seconds) {
            Queue::connection($this->connection)->laterOn($this->queue, $seconds, $job);
        } else {
            Queue::connection($this->connection)->pushOn($this->queue, $job);
        }
    }

    protected function debug(string $message, array $payload = [])
    {
        Log::info('RepricerService::Company('.$this->company->getKey().') - '.$message, $payload);
    }
}
