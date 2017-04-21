<?php

namespace App\Facades;

use App\Services\CurrencyConversionService;
use Illuminate\Support\Facades\Facade;

class Currency extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CurrencyConversionService::class;
    }
}
