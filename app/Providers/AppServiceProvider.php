<?php

namespace App\Providers;

use App\Contracts\Repositories\CompanyProductRepositoryContract;
use App\Contracts\Repositories\MarketplaceListingRepositoryContract;
use App\Contracts\Repositories\MarketplaceRepositoryContract;
use App\Managers\MarketplaceManager;
use App\Repositories\CompanyProductRepository;
use App\Repositories\MarketplaceListingRepository;
use App\Repositories\MarketplaceRepository;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Mongodb\Query\Builder;
use Maknz\Slack\Client as Slack;
use Queue;
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

        Queue::failing(function (JobFailed $event) {
            /** @var \App\Jobs\PriceUpdaterJob|\App\Jobs\PriceWatcherJob $job */
            $job = $event->job;

            resolve(Slack::class)->send(ucwords($job->getMarketplace()->name).' '.get_class($job).' failed for '.
                                        $job->getCompany()->name.'. Company ID: '.$job->getCompany()->getKey().
                                        ', Marketplace ID: '.$job->getMarketplace()->getKey());
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

        $this->registerFailedJobNotifier();
        $this->registerMarketplaceManager();
        $this->registerMarketplaceManager();
        $this->registerRepositories();

        $this->patchMongoBuilder();
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

    protected function registerFailedJobNotifier()
    {
        $this->app->singleton(Slack::class, function () {
            return new Slack(
                config('slack.endpoint'),
                [
                    'channel' => config('slack.channel'),
                    'username' => config('slack.username'),
                    'icon' => config('slack.icon'),
                    'link_names' => config('slack.link_names'),
                    'unfurl_links' => config('slack.unfurl_links'),
                    'unfurl_media' => config('slack.unfurl_media'),
                    'allow_markdown' => config('slack.allow_markdown'),
                    'markdown_in_attachments' => config('slack.markdown_in_attachments'),
                ],
                new Guzzle
            );
        });
    }

    protected function patchMongoBuilder()
    {
        if (App::environment('local', 'staging')) {
            Builder::macro('getName', function () {
                return 'mongodb';
            });
        }
    }
}
