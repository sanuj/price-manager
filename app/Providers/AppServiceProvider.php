<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $testing = [
        \Laravel\Dusk\DuskServiceProvider::class,
    ];

    protected $local = [
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
    }
}
