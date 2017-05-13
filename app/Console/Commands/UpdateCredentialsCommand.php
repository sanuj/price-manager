<?php

namespace App\Console\Commands;

use App\Company;
use App\Contracts\Repositories\MarketplaceRepositoryContract;
use App\Managers\MarketplaceManager;
use App\Marketplace;
use Illuminate\Console\Command;
use Validator;

class UpdateCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exponent:credentials {company} {marketplace}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update/store credentials for marketplace';
    /**
     * @var \App\Contracts\Repositories\MarketplaceRepositoryContract
     */
    private $repository;
    /**
     * @var \App\Managers\MarketplaceManager
     */
    private $manager;

    /**
     * Create a new command instance.
     *
     * @param \App\Contracts\Repositories\MarketplaceRepositoryContract $repository
     * @param \App\Managers\MarketplaceManager $manager
     */
    public function __construct(MarketplaceRepositoryContract $repository, MarketplaceManager $manager)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $company = Company::whereId($this->argument('company'))->firstOrFail();
        $marketplace = Marketplace::whereName($this->argument('marketplace'))->firstOrFail();

        $current = $company->credentialsFor($marketplace);

        $this->info('Company: '.$company->name);
        if (!is_null($current)) {
            $this->info('Current Values:');
            dump($current->credentials);
        }

        if ($this->confirm('Do you want to update credentials?') === false) {
            return;
        }

        $fields = $this->manager->driver($marketplace->name)->getCredentialRules();

        $credentials = $this->read($fields, $current->credentials ?? []);

        if (!is_null($current)) {
            $this->repository->updateForCompany($company, $marketplace, compact('credentials'));
        } else {
            $this->repository->createForCompany($company,
                compact('credentials') + ['marketplace_id' => $marketplace->getKey()]);
        }
    }

    protected function read(array $fields, array $defaults)
    {
        $values = [];

        foreach ($fields as $key => $field) {
            $values[$key] = $this->readField($key, $field, $defaults[$key] ?? null);
        }

        return $values;
    }

    protected function readField(string $key, array $field, $default = null)
    {
        do {
            $value = $this->ask($field['description'] ?? $key, $default ?? $field['default'] ?? null);
        } while (Validator::make([$key => $value], [$key => $field['rule'] ?? 'required'])->fails());

        return $value;
    }
}
