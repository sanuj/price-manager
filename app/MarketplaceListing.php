<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketplaceListing extends Model
{
    use Concerns\Revisionable;

    protected $fillable = [
        'selling_price',
        'cost_price',
        'min_price',
        'max_price',
        'uid',
        'sku',
        'url',
        'ref_num',
    ];

    protected $casts = [
        'selling_price' => 'int',
        'cost_price' => 'int',
        'min_price' => 'int',
        'max_price' => 'int',

        'marketplace_selling_price' => 'float',
        'marketplace_cost_price' => 'float',
        'marketplace_min_price' => 'float',
        'marketplace_max_price' => 'float',

        'company_product_id' => 'int',
    ];

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public function companyProduct()
    {
        return $this->belongsTo(CompanyProduct::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function priceSnapshots()
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
