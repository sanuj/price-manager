<?php

namespace App\Facades;

use App\Services\RepriceService;
use Illuminate\Support\Facades\Facade;

class Reprice extends Facade
{
    static protected function getFacadeAccessor()
    {
        return RepriceService::class;
    }
}
