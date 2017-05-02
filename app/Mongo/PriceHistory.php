<?php

namespace App\Mongo;

use Jenssegers\Mongodb\Eloquent\Model;

class PriceHistory extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'price_history';

    protected $guarded = [];
}
