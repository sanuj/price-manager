<?php

namespace App\Console\Commands;

use App\Company;
use App\Jobs\PriceUpdaterJob;
use App\Jobs\PriceWatcherJob;
use App\Marketplace;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class StartRepricingService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exponent:start';

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
        $this->call('exponent:stop');
        $total = Company::count();
        $count = 0;
        $this->line('Starting exponent price watcher & updater for '.$total.' companies.');
        Company::chunk(50, function (Collection $companies) use (&$count, $total) {
            $count += count($companies);

            $this->output->write($count.' of '.$total.'');
            $companies->each(function (Company $company) {
                $company->marketplaces->each(function (Marketplace $marketplace) use ($company) {
                    $this->output->write('.');
                    dispatch(new PriceWatcherJob($company, $marketplace));
                    $this->output->write('.');
                    dispatch(new PriceUpdaterJob($company, $marketplace));
                    $this->output->write('.');
                });
            });
            $this->line('done.');
        });
    }
}
