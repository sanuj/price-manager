<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyMarketplace extends Pivot
{
    protected $table = 'company_marketplace';

    protected $fillable = ['credentials'];

    protected $guarded = ['credentials'];

    protected $decryptedCredentials;

    public function getCredentialsAttribute()
    {
        if (is_null($this->decryptedCredentials)) {
            $this->decryptedCredentials = json_decode(decrypt($this->attributes['credentials']), true);
        }

        return $this->decryptedCredentials;
    }

    public function setCredentialsAttribute($credentials)
    {
        if (is_string($credentials)) {
            $this->attributes['credentials'] = $credentials;
        } else {
            $this->attributes['credentials'] = encrypt(json_encode($credentials));
        }
        $this->decryptedCredentials = null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class);
    }

    public static function fromRawAttributes(Model $parent, $attributes, $table, $exists = false)
    {
        return new static($parent, $attributes, $table, $exists);
    }
}
