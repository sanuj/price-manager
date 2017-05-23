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
        // TODO: Fetch these from redis and make the exponents dynamic.
        $increment_exponent = 1;
        $decrement_exponent = 1;

        if ($this->buyBoxShareIsHigh()) {
            $predicted_price = $this->selling_price + pow($increment_base, $increment_exponent);
        } else {
            $predicted_price = $this->selling_price - pow($decrement_base, $decrement_exponent);
        }

        return $this->tamePredictedPrice($listing, $predicted_price);
    }

}
