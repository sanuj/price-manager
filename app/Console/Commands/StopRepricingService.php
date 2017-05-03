<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Queue;

class StopRepricingService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repricer:stop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old repricer jobs.';

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
    }

    public function handle()
    {
        $this->purge('exponent-watch');
        $this->purge('exponent-update');
    }

    protected function purge(string $queue)
    {
        $count = Queue::connection()->size($queue);

        while ($count--) {
            Queue::connection()->pop($queue);
        }
    }
}
