<?php

namespace Tests\Unit\Jobs;

use App\Company;
use App\CompanyProduct;
use App\Jobs\PriceWatcherJob;
use App\Marketplace;
use App\MarketplaceListing;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Queue;
use Tests\TestCase;

class PriceWatcherJobTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_be_created()
    {
        // 1. Create required data.
        $marketplace = $this->createMarketplace();
        $company = $this->createCompany();
        $products = $this->createProducts($company, 22);
        $this->listProducts($company, $marketplace, $products);

        // 2. Capture rescheduled jobs.
        Queue::fake();

        // 3. Run job.
        $job = new PriceWatcherJob($company, $marketplace);

        $job->handle();

        $this->assertCount(2,
            MarketplaceListing::where(
                'updated_at',
                '<',
                Carbon::now()->addMinutes(-1))->get()
        );

        Queue::assertPushedOn('exponent-watch', PriceWatcherJob::class);
    }

    /**
     * @return \App\Company
     */
    protected function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    /**
     * @return \App\Marketplace
     */
    protected function createMarketplace(): Marketplace
    {
        return factory(Marketplace::class)->create();
    }

    /**
     * @param \App\Company $company
     * @param int $count
     *
     * @return CompanyProduct[]|Collection
     */
    protected function createProducts(Company $company, int $count = 1)
    {
        return factory(CompanyProduct::class, $count)->create([
            'company_id' => $company->getKey(),
        ]);
    }

    /**
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     * @param \Illuminate\Support\Collection $products
     *
     * @return MarketplaceListing[]|Collection
     */
    protected function listProducts(Company $company, Marketplace $marketplace, Collection $products)
    {
        if (!$company->credentialsFor($marketplace)) {
            $company->addOrUpdateMarketplace($marketplace, [
                'credentials' => ['foo' => 'bar'],
            ]);
        }

        return $products->map(function (CompanyProduct $product) use ($company, $marketplace) {
            return factory(MarketplaceListing::class)->create([
                'company_product_id' => $product->getKey(),
                'company_id' => $company->getKey(),
                'marketplace_id' => $marketplace->getKey(),
                'updated_at' => Carbon::now()->addMinutes(-16),
            ]);
        });
    }
}
