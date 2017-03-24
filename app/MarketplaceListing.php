<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function listing()
    {
        return $this->belongsTo(CompanyProduct::class);
    }

    public function priceSnapshots()
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
