<?php

namespace App\Providers;

use App\{
    Company, CompanyProduct
};

class TrustServiceProvider extends \Znck\Trust\TrustServiceProvider
{
    protected $models = [
        Company::class,
        CompanyProduct::class,
    ];
}
