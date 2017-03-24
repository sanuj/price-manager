<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public function referrer()
    {
        return $this->belongsTo(User::class);
    }

    public function marketplaces()
    {
        return $this->belongsToMany(Marketplace::class)
                    ->using(CompanyMarketplace::class);
    }
}
