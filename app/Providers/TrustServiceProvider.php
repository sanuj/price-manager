<?php

namespace App\Providers;

use App\{
    CompanyProduct, Marketplace
};

class TrustServiceProvider extends \Znck\Trust\TrustServiceProvider
{
    protected $models = [
        CompanyProduct::class,
        Marketplace::class,
    ];
}
