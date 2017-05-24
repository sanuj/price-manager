<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\MarketplaceListing;

class ExponentialBuyBoxShareHeuristicPrice extends BuyBoxShareHeuristicPrice  implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
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

        $listing->repricing_algorithm['params']['increment_exponent'] = $increment_exponent;
        $listing->repricing_algorithm['params']['decrement_exponent'] = $decrement_exponent;
        $listing->repricing_algorithm['params']['last_bbs'] = $last_bbs;

        return $this->tamePredictedPrice($listing, $predicted_price);
    }

}
