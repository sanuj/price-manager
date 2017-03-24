<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceSnapshot extends Model
{
    protected $casts = [
        'selling_price' => 'int',
        'cost_price' => 'int',
        'min_price' => 'int',
        'max_price' => 'int',

        'marketplace_selling_price' => 'float',
        'marketplace_cost_price' => 'float',
        'marketplace_min_price' => 'float',
        'marketplace_max_price' => 'float',
    ];

    public function marketplaceListing()
    {
        return $this->belongsTo(MarketplaceListing::class);
    }
}
