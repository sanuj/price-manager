<?php

namespace App\Console\Commands;

use App\Company;
use App\Jobs\RepricingJob;
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
     *
     * @param \App\Managers\MarketplaceManager $manager
     */
    public function __construct(MarketplaceManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->queue = config('queue.repricer');
    }

    public function handle()
    {
        $this->call('repricer:stop');

        Company::chunk(50, function (Collection $companies) {
            $jobs = $companies->reduce(function (Company $company) {
                return $company->marketplaces->map(function (Marketplace $marketplace) use ($company) {
                    $job = new RepricingJob($company, $marketplace, $this->manager);

                    // TODO: Configure frequency & rate for the marketplace/company.

                    return $job;
                });
            }, []);

            Queue::connection($this->queue)->bulk($jobs);
        });
    }
}
