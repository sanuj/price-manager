<?php

namespace App\Jobs;

use App\Company;
use App\CompanyProduct;
use App\Marketplace;
use App\MarketplaceListing;
use Auth;
use DB;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Log;
use Validator;

class ImportMarketplaceListingJob
{
    /**
     * @var \App\Company
     */
    protected $company;
    /**
     * @var \App\Marketplace
     */
    private $marketplace;
    /**
     * @var \App\User
     */
    private $user;
    /**
     * @var string
     */
    private $filename;

    /**
     * ImportMarketplaceListingJob constructor.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     * @param string $filename
     */
    public function __construct(Company $company, Marketplace $marketplace, string $filename)
    {
        $this->company = $company;
        $this->marketplace = $marketplace;
        $this->filename = $filename;
        $this->user = Auth::user();
    }

    public function handle()
    {
        $this->authenticate();

        list($entries, $header) = $this->load();

        $invalid = [];

        DB::beginTransaction();

        foreach ($entries as $entry) {
            $entry = $this->map($entry, $header);

            if ($this->isValidEntry($entry)) {
                $this->import($entry);
            } else {
                $invalid[] = $entry;
            }
        }

        if (count($invalid)) {
            if ($this->isRunningInConsole()) {
                $this->dumpFailed($header, $invalid);
            } else {
                DB::rollBack();

                $this->debug('Invalid entries in file: '.$this->filename, $invalid);
                throw new Exception('Invalid entries in CSV');
            }
        }

        DB::commit();
    }

    protected function validateCSV($entries)
    {
        if (!is_array($entries)) {
            throw new InvalidArgumentException('Failed to read CSV file.');
        }

        if (count($entries) < 2) {
            throw new InvalidArgumentException('CSV file should should have at least one row.');
        }
    }

    protected function validateHeader(array $header)
    {
        Validator::validate(array_flip($header), [
            'sku' => 'required',
            'uid' => 'required',
            'min_price' => 'required',
            'max_price' => 'required',
        ]);
    }

    protected function isValidEntry(array $entry)
    {
        return Validator::make($entry, [
            'sku' => 'required|min:1',
            'uid' => 'required|min:1',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'status' => 'optional|in:0,1',
            'algorithm:selector' => 'optional|in:'.join(',', array_keys(config('pricing.selectors'))),
            'algorithm:increment_factor' => 'optional|numeric|min:0|max:0.25',
            'algorithm:decrement_factor' => 'optional|numeric|min:0|max:0.25',
            'algorithm:multiplier' => 'optional|numeric',
            'algorithm:bbs_hours' => 'optional|numeric',
        ])->passes();
    }

    protected function map(array $entry, array $header)
    {
        $result = [];

        foreach ($header as $index => $field) {
            $result[$field] = $entry[$index] ?? null;
        }

        return $result;
    }

    protected function import(array $entry)
    {
        $product = $this->getProduct($entry['sku']);
        $listing = $this->getListing($product);

        $listing->marketplace_min_price = $entry['min_price'];
        $listing->marketplace_max_price = $entry['max_price'];
        $listing->uid = $entry['uid'];
        $listing->status = $entry['status'] ?? $listing->status ?? 0;
        $listing->repricing_algorithm = array_merge($listing->repricing_algorithm, $this->collect($entry, 'algorithm'));

        if (!$listing->save()) {
            throw new Exception('Could not create a listing.');
        }
    }

    /**
     * Find or create a product.
     *
     * @param string $sku
     *
     * @return \App\CompanyProduct
     * @throws Exception
     */
    protected function getProduct(string $sku): CompanyProduct
    {
        $product = CompanyProduct::whereCompanyId($this->company->getKey())
                                 ->whereSku($sku)
                                 ->first();

        if (is_null($product)) {
            $product = new CompanyProduct(compact('sku'));
            $product->name = 'NOT SET';
            $product->company()->associate($this->company);

            if (!$product->save()) {
                throw new Exception('Could not create a product.');
            }
        }

        return $product;
    }

    protected function getListing(CompanyProduct $product): MarketplaceListing
    {
        $listing = MarketplaceListing::whereMarketplaceId($this->marketplace->getKey())
                                     ->whereCompanyId($this->company->getKey())
                                     ->whereCompanyProductId($product->getKey())
                                     ->first();

        if (is_null($listing)) {
            $listing = new MarketplaceListing();

            $listing->marketplace()->associate($this->marketplace);
            $listing->company()->associate($this->company);
            $listing->companyProduct()->associate($product);
        }

        return $listing;
    }

    protected function collect(array $entry, string $prefix): array
    {
        return collect($entry)->filter(function ($_, $key) use ($prefix) {
            return Str::startsWith($key, $prefix.':');
        })->toArray();
    }

    /**
     * @param $header
     * @param $invalid
     */
    protected function dumpFailed(array $header, array $invalid)
    {
        array_unshift($invalid, $header);
        $filename = storage_path("{$this->company->id}-{$this->marketplace->name}.".time().'.csv');
        $fp = fopen($filename, 'w');

        foreach ($invalid as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        echo 'Failed entries stored in: '.$filename.PHP_EOL;
    }

    /**
     * @return bool
     */
    protected function isRunningInConsole(): bool
    {
        return is_null($this->user);
    }

    protected function authenticate(): void
    {
        if (!is_null($this->user)) {
            Auth::login($this->user);
        }
    }

    /**
     * @return array
     */
    protected function load(): array
    {
        $entries = array_map('str_getcsv', file($this->filename));

        $this->validateCSV($entries);

        $header = array_shift($entries);

        $this->validateHeader($header);

        return [$entries, $header];
    }

    protected function debug(string $message, $payload = [])
    {
        Log::debug(self::class." Company({$this->company->id}).Marketplace({$this->marketplace->id})::".$message,
            $payload);
    }
}
