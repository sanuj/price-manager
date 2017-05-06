<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\Exceptions\InvalidMinPriceException;
use App\Exceptions\NoSnapshotsAvailableException;
use App\Exceptions\NoSnapshotsWithOffersException;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;

class LinearCombinationScoreHeuristicPrice implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
    {
        if($listing->min_price == 0)
            throw new InvalidMinPriceException($listing);

        $buy_box_share = $this->getBuyBoxShare($listing->id);

        $quantum = 20;
        if($this->buyBoxShareIsHigh($buy_box_share)) {
            $predicted_price = $listing->marketplace_selling_price + 2*$quantum;
        }
        else if($this->buyBoxShareIsLow($buy_box_share)) {
            $predicted_price = $listing->marketplace_selling_price - $quantum;
        }
        else {
            $predicted_price = $listing->marketplace_selling_price + $quantum;
        }

        return min($listing->max_price, max($predicted_price, $listing->min_price));
    }

    protected function buyBoxShareIsHigh($buy_box_share) {
        return $buy_box_share > 0.6;
    }

    protected function buyBoxShareIsLow($buy_box_share) {
        return $buy_box_share < 0.4;
    }

    public function getBuyBoxShare($marketplace_listing_id, $num_days=1)
    {
        $snapshots = $this->buyBoxSnapshots($marketplace_listing_id, $num_days);

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

        return $snapshots_with_buybox->count() / $snapshots_with_offers->count();
    }

    protected function buyBoxSnapshots($marketplace_listing_id, $num_days) {
        return Snapshot::where('marketplace_listing_id', $marketplace_listing_id)
            ->where('updated_at', '>', Carbon::now()->subDays($num_days))
            ->get();
    }
}
