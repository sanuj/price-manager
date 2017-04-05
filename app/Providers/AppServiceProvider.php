<?php

namespace App\Providers;

use App\Contracts\Repositories\CompanyProductRepositoryContract;
use App\Managers\MarketplaceManager;
use App\Repositories\CompanyProductRepository;
use Illuminate\Support\ServiceProvider;

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
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('testing')) {
            foreach ($this->testing as $provider) {
                $this->app->register($provider);
            }
        }

        if ($this->app->environment('local')) {
            foreach ($this->local as $provider) {
                $this->app->register($provider);
            }
        }

        $this->app->singleton(MarketplaceManager::class);

        $this->registerRepositories();
    }

    protected function registerRepositories(): void
    {
        $repositories = [
            CompanyProductRepositoryContract::class => CompanyProductRepository::class,
        ];

        foreach ($repositories as $abstract => $concrete) {
            $this->app->singleton($abstract, $concrete);
        }
    }
}
