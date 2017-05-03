<?php

namespace App\Console\Commands;

use App\Company;
use App\Jobs\PriceUpdaterJob;
use App\Jobs\PriceWatcherJob;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Queue;

class StartRepricingService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repricer:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run repricer jobs.';

    /**
     * @var \App\Managers\MarketplaceManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $queue;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->queue = config('queue.repricer');
    }

    public function handle()
    {
        $this->call('repricer:stop');

        Company::chunk(50, function (Collection $companies) {
            $jobs = $companies->reduce(function ($jobs, Company $company) {
                return array_merge($jobs,
                    $company->marketplaces->map(function (Marketplace $marketplace) use ($company) {
                        $job = new PriceWatcherJob($company, $marketplace);

                        return $job;
                    })->toArray(),
                    $company->marketplaces->map(function (Marketplace $marketplace) use ($company) {
                        $job = new PriceUpdaterJob($company, $marketplace);

                        return $job;
                    })->toArray()
                );
            }, []);

            Queue::connection($this->queue)->bulk($jobs);
        });
    }
}
