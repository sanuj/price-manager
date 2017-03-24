<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceSnapshot extends Model
{
    public function marketplaceListing()
    {
        return $this->belongsTo(MarketplaceListing::class);
    }
}
