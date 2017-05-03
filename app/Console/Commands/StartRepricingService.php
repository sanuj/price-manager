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
    protected $connectionName;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->connectionName = config('queue.repricer');
    }

    public function handle()
    {
        $this->call('repricer:stop');
        $this->line('Starting exponent price watcher & updater.');
        Company::chunk(50, function (Collection $companies) {
            $jobs = $companies->reduce(function ($jobs, Company $company) {
                return array_merge($jobs,
                    $company->marketplaces->map(function (Marketplace $marketplace) use ($company) {
                        $job = new PriceWatcherJob($company, $marketplace);
                        $job->onConnection($this->connectionName);
                        $job->onQueue('exponent-watch');

                        return $job;
                    })->toArray(),
                    $company->marketplaces->map(function (Marketplace $marketplace) use ($company) {
                        $job = new PriceUpdaterJob($company, $marketplace);
                        $job->onConnection($this->connectionName);
                        $job->onQueue('exponent-update');

                        return $job;
                    })->toArray()
                );
            }, []);

            Queue::connection($this->connectionName)->bulk($jobs);
        });
    }
}
