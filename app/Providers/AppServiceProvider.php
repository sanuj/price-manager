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
use Transformer;

class AppServiceProvider extends ServiceProvider
{
    protected $testing = [
        \Laravel\Dusk\DuskServiceProvider::class,
    ];

    protected $local = [
        \Laravel\Tinker\TinkerServiceProvider::class,
        \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Znck\Transform\Facades\Transform::register(function (Model $model) {
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

        $this->app->singleton(MarketplaceManager::class, function () {
            return new MarketplaceManager($this->app);
        });

        $this->registerRepositories();
    }

    protected function registerRepositories()
    {
        $repositories = [
            CompanyProductRepositoryContract::class => CompanyProductRepository::class,
            MarketplaceRepositoryContract::class => MarketplaceRepository::class,
            MarketplaceListingRepositoryContract::class => MarketplaceListingRepository::class,
        ];

        foreach ($repositories as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }
    }

    protected function configureTestingEnv()
    {
        if ($this->app->environment('testing')) {
            foreach ($this->testing as $provider) {
                $this->app->register($provider);
            }
        }
    }

    protected function configureDevEnv()
    {
        if ($this->app->environment('local')) {
            foreach ($this->local as $provider) {
                $this->app->register($provider);
            }
        }
    }
}
