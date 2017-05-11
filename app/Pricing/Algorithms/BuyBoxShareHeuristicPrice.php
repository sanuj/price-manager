<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\Exceptions\InvalidMinPriceException;
use App\Exceptions\InvalidMaxPriceException;
use App\Exceptions\NoSnapshotsAvailableException;
use App\Exceptions\NoSnapshotsWithOffersException;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BuyBoxShareHeuristicPrice implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
    {
        $min_price = $listing->marketplace_min_price;
        $max_price = $listing->marketplace_max_price;
        $selling_price = $listing->marketplace_selling_price;
        $increment_factor = $listing->repricing_algorithm['params']['increment_factor'] ?? 0.05;
        $decrement_factor = $listing->repricing_algorithm['params']['decrement_factor'] ?? 0.05;
        $multiplier = $listing->repricing_algorithm['params']['multiplier'] ?? 2;

        if($min_price == 0)
            throw new InvalidMinPriceException($listing);

        if($max_price == 0 or $max_price < $min_price)
            throw new InvalidMaxPriceException($listing);

        $buy_box_share = $this->getBuyBoxShare($listing->id);

        if($this->buyBoxShareIsHigh($buy_box_share))
            $predicted_price = $selling_price + $multiplier * $increment_factor * $selling_price;
        else if($this->buyBoxShareIsLow($buy_box_share))
            $predicted_price = $selling_price - $decrement_factor * $selling_price;
        else
            $predicted_price = $selling_price + $increment_factor * $selling_price;

        return min($max_price, max($predicted_price, $min_price));
    }

    protected function buyBoxShareIsHigh($buy_box_share) {
        return $buy_box_share > 0.6;
    }

    protected function buyBoxShareIsLow($buy_box_share) {
        return $buy_box_share < 0.3;
    }

    public function getBuyBoxShare($marketplace_listing_id, $num_hours=3)
    {
        $snapshots = $this->buyBoxSnapshots($marketplace_listing_id, $num_hours);

        if($snapshots->count() == 0)
            throw new NoSnapshotsAvailableException($marketplace_listing_id);

        $snapshots_with_offers = $snapshots->filter(function ($value, $key) {
            return count($value->offers) > 0;
        });

        if($snapshots_with_offers->count() == 0)
            throw new NoSnapshotsWithOffersException($marketplace_listing_id);

        $snapshots_with_buybox = $snapshots_with_offers->filter(function ($value, $key) {
            return (bool)$value->offers[0]['has_buy_box'] === true;
        });

        return ($snapshots_with_buybox->count() * 1.0) / $snapshots_with_offers->count();
    }

    protected function buyBoxSnapshots($marketplace_listing_id, $num_hours) : Collection {
        return Snapshot::where('marketplace_listing_id', $marketplace_listing_id)
            ->where('updated_at', '>', Carbon::now()->subHours($num_hours))
            ->get();
    }
}
