<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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

    public function setPriceAttribute($price)
    {
        $this->attributes['price'] = $price;
        // Convert Paise to Currency.
        if ($this->exists) {
            $this->attributes['marketplace_price'] = $price;
            $this->attributes['marketplace_currency'] = $this->marketplace->currency;
            $this->attributes['marketplace_price_updated_at'] = Carbon::now()->timestamp;
        }
    }

    /**
     * Convert Paise to INR.
     *
     * @param int $paise
     *
     * @return float
     */
    protected function toRupees(int $paise): float
    {
        return 0.01 * $paise; // To INR.
    }

    /**
     * Convert INR to Paise.
     *
     * @param int|float $rupees
     *
     * @return int
     */
    protected function toPaise($rupees): int
    {
        return (int)($rupees * 100);
    }

    protected function paiseToCurrency(int $price, string $currency): float
    {
        if ($currency === 'INR') {
            return $price * 0.01;
        }

        throw new InvalidArgumentException("Unknown currency: ${currency}. Cannot convert");
    }
}
