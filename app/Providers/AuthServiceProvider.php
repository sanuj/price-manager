<?php

namespace App\Providers;

use App\CompanyProduct;
use App\Marketplace;
use App\Mongo\Snapshot;
use App\Policies\{
    CompanyProductPolicy, MarketplacePolicy, SnapshotPolicy
};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        CompanyProduct::class => CompanyProductPolicy::class,
        Marketplace::class => MarketplacePolicy::class,
        Snapshot::class => SnapshotPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
