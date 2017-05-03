<?php

namespace App\Managers;

use App\Drivers\Marketplace\AmazonIndiaDriver;
use App\Drivers\Marketplace\FakeMarketplaceDriver;
use Illuminate\Support\Manager;

class MarketplaceManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return config('marketplace.default');
    }

    public function createAmazonInDriver()
    {
        return new AmazonIndiaDriver(config('marketplace.connections.amazon-in'));
    }

    public function createFakeDriver()
    {
        return new FakeMarketplaceDriver();
    }
}
