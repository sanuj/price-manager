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
            $companies->each(function (Company $company) {
                $company->marketplaces->each(function (Marketplace $marketplace) use ($company) {
                    $watcher = new PriceWatcherJob($company, $marketplace);
                    $watcher->onConnection($this->connectionName);
                    $watcher->onQueue('exponent-watch');
                    Queue::connection($watcher->connection)->pushOn($watcher->queue, $watcher);

                    $updater = new PriceUpdaterJob($company, $marketplace);
                    $updater->onConnection($this->connectionName);
                    $updater->onQueue('exponent-update');
                    Queue::connection($updater->connection)->pushOn($updater->queue, $updater);
                });
            });
        });
    }
}
