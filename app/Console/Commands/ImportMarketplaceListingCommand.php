<?php

namespace App\Console\Commands;

use App\Company;
use App\Jobs\ImportMarketplaceListingJob;
use App\Marketplace;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportMarketplaceListingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exponent:import {company} {marketplace} {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import marketplace listing.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $company = Company::firstOrFail($this->argument('company'));
        $marketplace = Marketplace::whereName($this->argument('marketplace'))->firstOrFail();
        $filename = $this->resolve($this->argument('filename'));

        if (!file_exists($filename)) {
            throw new Exception($filename.' not found.');
        }

        dispatch(new ImportMarketplaceListingJob($company, $marketplace, $filename));
    }

    protected function resolve(string $filename)
    {
        if (Str::startsWith($filename, '/')) {
            return realpath($filename);
        }

        return realpath(getcwd().DIRECTORY_SEPARATOR.$filename);
    }
}
