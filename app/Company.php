<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Concerns\Revisionable;

    protected $fillable = [
        'name',
        'referrer_id',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class);
    }

    public function marketplaces()
    {
        return $this->belongsToMany(Marketplace::class)
                    ->withPivot('id', 'credentials')
                    ->withTimestamps()
                    ->using(CompanyMarketplace::class);
    }

    /**
     * Get pivot entry for a marketplace.
     *
     * @param \App\Marketplace $marketplace
     *
     * @return \App\CompanyMarketplace
     */
    public function credentialsFor(Marketplace $marketplace)
    {
        return $this->marketplaces()
                    ->wherePivot('marketplace_id', $marketplace->getKey())
                    ->first()->pivot;
    }
}
