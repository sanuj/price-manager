<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $this->queue = config('queue.repricer');
    }

    public function handle()
    {
        $count = Queue::connection($this->queue)->size();

        while ($count--) {
            Queue::connection($this->queue)->pop();
        }
    }
}
