<?php

namespace App\Providers;

use App\Contracts\Repositories\CompanyProductRepositoryContract;
use App\Contracts\Repositories\MarketplaceListingRepositoryContract;
use App\Contracts\Repositories\MarketplaceRepositoryContract;
use App\Managers\MarketplaceManager;
use App\Repositories\CompanyProductRepository;
use App\Repositories\MarketplaceListingRepository;
use App\Repositories\MarketplaceRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Znck\Transform\Facades\Transform;

class AppServiceProvider extends ServiceProvider
{
    protected $localServiceProviders = [
        \Laravel\Tinker\TinkerServiceProvider::class,
        \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    ];

    protected $testingServiceProviders = [
        \Laravel\Dusk\DuskServiceProvider::class,
    ];

    protected $productionServiceProviders = [];

    protected $repositories = [
        CompanyProductRepositoryContract::class => CompanyProductRepository::class,
        MarketplaceRepositoryContract::class => MarketplaceRepository::class,
        MarketplaceListingRepositoryContract::class => MarketplaceListingRepository::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Transform::register(function (Model $model) {
            return [
                'id' => $model->getKey(),
                '_type' => $model->getMorphClass(),
            ];
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configureTestingEnv();
        $this->configureDevEnv();
        $this->configureProductionEnv();

        $this->registerMarketplaceManager();
        $this->registerRepositories();
    }

    protected function configureTestingEnv()
    {
        if ($this->app->environment('testing')) {
            foreach ($this->testingServiceProviders as $provider) {
                $this->app->register($provider);
            }
        }
    }

    protected function configureDevEnv()
    {
        if ($this->app->environment('local')) {
            foreach ($this->localServiceProviders as $provider) {
                $this->app->register($provider);
            }
        }
    }

    protected function configureProductionEnv()
    {
        if ($this->app->environment('production')) {
            foreach ($this->productionServiceProviders as $provider) {
                $this->app->register($provider);
            }
        }
    }

    protected function registerMarketplaceManager()
    {
        $this->app->singleton(MarketplaceManager::class, function () {
            return new MarketplaceManager($this->app);
        });
    }

    protected function registerRepositories()
    {
        foreach ($this->repositories as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }
    }
}
