<?php

namespace App\Jobs;

use App\Marketplace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

abstract class SelfSchedulingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var \App\Marketplace
     */
    public $marketplace;

    /**
     * @return \App\Marketplace
     */
    public function getMarketplace(): Marketplace
    {
        return $this->marketplace;
    }

    /**
     * @var \App\Company
     */
    public $company;

    /**
     * @return \App\Company
     */
    public function getCompany(): \App\Company
    {
        return $this->company;
    }

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
        $this->debug("Rescheduling (after ${seconds} seconds)");
        dispatch(with(new static($this->company, $this->marketplace))->delay($seconds));
    }

    protected function debug(string $message, $payload = [])
    {
        Log::debug(get_class($this).'::Company('.$this->company->getKey().') - '.$message, (array)$payload);
    }
}
