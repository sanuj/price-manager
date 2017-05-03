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
        $result = $this->marketplaces()
                       ->wherePivot('marketplace_id', $marketplace->getKey())
                       ->first();

        return $result ? $result->pivot : null;
    }

    public function addOrUpdateMarketplace(Marketplace $marketplace, array $attributes): bool
    {
        $pivot = $this->credentialsFor($marketplace);

        if (!$pivot) {
            /** @var \App\CompanyMarketplace $pivot */
            $pivot = $this->marketplaces()->newPivot();

            $pivot->company()->associate($this);
            $pivot->marketplace()->associate($marketplace);
        }

        $pivot->fill($attributes);

        return $pivot->save();
    }
}
