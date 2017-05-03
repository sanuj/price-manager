<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Queue;

abstract class SelfSchedulingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Bundle multiple reprice requests.
     *
     * @var int
     */
    protected $perRequestCount = 1000;
    /**
     * Number of minutes after which listing is repriced.
     *
     * @var int
     */
    protected $frequency = 15;

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
    }
}
