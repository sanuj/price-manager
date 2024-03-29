<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\MarketplaceListing;
use Illuminate\Support\Facades\Log;

class ExponentialBuyBoxShareHeuristicPrice extends BuyBoxShareHeuristicPrice  implements PricingAlgorithmContract
{

    public function calculatePrice(MarketplaceListing $listing, $silent=false): float
    {
        $this->init($listing);

        $increment_base = $listing->repricing_algorithm['params']['increment_base'] ?? 2;
        $decrement_base = $listing->repricing_algorithm['params']['decrement_base'] ?? 2;
        $increment_exponent = $listing->repricing_algorithm['params']['increment_exponent'] ?? 1;
        $decrement_exponent = $listing->repricing_algorithm['params']['decrement_exponent'] ?? 1;
        $last_bbs = $listing->repricing_algorithm['params']['last_bbs'] ?? 1;

        if ($this->buyBoxShareIsHigh()) {
            if($last_bbs === 0) {
                $last_selling_price = $this->selling_price + pow($decrement_base, $decrement_exponent-1);
                $predicted_price = $last_selling_price + pow($increment_base, $increment_exponent);
            } else {
                $predicted_price = $this->selling_price + pow($increment_base, $increment_exponent);
            }
            $decrement_exponent = 1;
            $increment_exponent++;
            $last_bbs = 1;
        } else {
            $predicted_price = $this->selling_price - pow($decrement_base, $decrement_exponent);
            $decrement_exponent++;
            $increment_exponent = 1;
            $last_bbs = 0;
        }

        // $listing->repricing_algorithm['params']['increment_exponent'] = $increment_exponent;
        // ^ This won't work, so using array_merge_recursive
        $listing->repricing_algorithm = array_merge($listing->repricing_algorithm, [
            'params' => compact('increment_exponent', 'decrement_exponent', 'last_bbs')
        ]);

        if(!$silent) {
            Log::debug(get_class($this).'::MarketplaceListingId('.$listing->id.') - bbs: '.$this->bbs
                .', predicted_price: '.$predicted_price, $listing->repricing_algorithm['params'] ?? []);
        }

        return $predicted_price;
    }

}
