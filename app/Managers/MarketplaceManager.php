<?php

namespace App\Managers;

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

    public function createAmazonInDriver() {

    }
}
