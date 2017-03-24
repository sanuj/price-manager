<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyProduct extends Model
{
    use Concerns\Revisionable;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
