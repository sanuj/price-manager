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
use Illuminate\Support\Facades\Log;

class BuyBoxShareHeuristicPrice implements PricingAlgorithmContract
{

    public function predict(MarketplaceListing $listing): float
    {
        $this->validateListing($listing);
        // If in future marketplace_min_price --> min_price then also update validateListing().
        $min_price = $listing->marketplace_min_price;
        $max_price = $listing->marketplace_max_price;
        $selling_price = $listing->marketplace_selling_price;
        $increment_factor = $listing->repricing_algorithm['params']['increment_factor'] ?? 0.05;
        $decrement_factor = $listing->repricing_algorithm['params']['decrement_factor'] ?? 0.05;
        $multiplier = $listing->repricing_algorithm['params']['multiplier'] ?? 2;
        $bbs_hours = $listing->repricing_algorithm['params']['bbs_hours'] ?? 1;

        $buy_box_share = $this->getBuyBoxShare($listing, $bbs_hours);

        if ($this->buyBoxShareIsHigh($buy_box_share)) {
            $predicted_price = $selling_price + $multiplier * $increment_factor * $selling_price;
        } else {
            if ($this->buyBoxShareIsLow($buy_box_share)) {
                $predicted_price = $selling_price - $decrement_factor * $selling_price;
            } else {
                $predicted_price = $selling_price + $increment_factor * $selling_price;
            }
        }

        Log::debug(get_class($this).'::MarketplaceListingId('.$listing->id.') - bbs: '.$buy_box_share
            .', predicted_price: '.$predicted_price, $listing->repricing_algorithm['params'] ?? []);

        return min($max_price, max($predicted_price, $min_price));
    }

    protected function validateListing(MarketplaceListing $listing) {
        if ($listing->marketplace_min_price == 0) {
            throw new InvalidMinPriceException($listing);
        }

        if ($listing->marketplace_max_price == 0 or
            $listing->marketplace_max_price < $listing->marketplace_min_price) {
            throw new InvalidMaxPriceException($listing);
        }
    }

    protected function buyBoxShareIsHigh($buy_box_share)
    {
        return $buy_box_share > 0.6;
    }

    protected function buyBoxShareIsLow($buy_box_share)
    {
        return $buy_box_share < 0.3;
    }

    public function getBuyBoxShare(MarketplaceListing $marketplace_listing, $num_hours)
    {
        $snapshots = $this->buyBoxSnapshots($marketplace_listing->id, $num_hours);

        if ($snapshots->count() == 0) {
            throw new NoSnapshotsAvailableException($marketplace_listing);
        }

        $snapshots_with_offers = $snapshots->filter(function ($value, $key) {
            return count($value->offers) > 0;
        });

        if ($snapshots_with_offers->count() == 0) {
            throw new NoSnapshotsWithOffersException($marketplace_listing);
        }

        $snapshots_with_buybox = $snapshots_with_offers->filter(function ($value, $key) {
            return (bool)$value->offers[0]['has_buy_box'] === true;
        });

        Log::debug(get_class($this).'::MarketplaceListingId('.$marketplace_listing->id.') - snapshots with buybox: '
            . $snapshots_with_buybox->count().', snapshots with offers: ' . $snapshots_with_offers->count()
            . ', num of hours: ' . $num_hours);

        return ($snapshots_with_buybox->count() * 1.0) / $snapshots_with_offers->count();
    }

    protected function buyBoxSnapshots($marketplace_listing_id, $num_hours): Collection
    {
        return Snapshot::where('marketplace_listing_id', $marketplace_listing_id)
                       ->where('updated_at', '>', Carbon::now()->subMinutes($num_hours*60))
                       ->get();
    }

}
