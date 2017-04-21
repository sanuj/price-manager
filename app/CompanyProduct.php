<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyProduct extends Model
{
    use Concerns\Revisionable;

    protected $fillable = ['name', 'sku'];

    protected $casts = [
        'company_id' => 'int',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
