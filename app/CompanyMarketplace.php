<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyMarketplace extends Pivot
{
    protected $table = 'company_marketplace';

    protected $decryptedCredentials;

    public function getCredentialsAttribute()
    {
        if (is_null($this->decryptedCredentials)) {
            $this->decryptedCredentials = json_decode(decrypt($this->attributes['credentials']), true);
        }

        return $this->decryptedCredentials;
    }

    public function setCredentialsAttribute(array $credentials)
    {
        $this->attributes['credentials'] = encrypt(json_encode($credentials));
        $this->decryptedCredentials = null;
    }
}
