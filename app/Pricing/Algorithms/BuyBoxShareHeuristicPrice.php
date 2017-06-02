<?php

namespace App\Pricing\Algorithms;

use App\Contracts\PricingAlgorithmContract;
use App\Exceptions\NoSnapshotsAvailableException;
use App\Exceptions\NoSnapshotsWithOffersException;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class BuyBoxShareHeuristicPrice extends BasePrice  implements PricingAlgorithmContract
{
    protected $bbs_hours = 1;
    protected $bbs = null;

    public function calculatePrice(MarketplaceListing $listing, $silent=false): float
    {
        $this->init($listing);

        $increment_factor = $listing->repricing_algorithm['params']['increment_factor'] ?? 0.05;
        $decrement_factor = $listing->repricing_algorithm['params']['decrement_factor'] ?? 0.05;
        $multiplier = $listing->repricing_algorithm['params']['multiplier'] ?? 2;

        if ($this->buyBoxShareIsHigh()) {
            $predicted_price = $this->selling_price + $multiplier * $increment_factor * $this->selling_price;
        } else {
            if ($this->buyBoxShareIsLow()) {
                $predicted_price = $this->selling_price - $decrement_factor * $this->selling_price;
            } else {
                $predicted_price = $this->selling_price + $increment_factor * $this->selling_price;
            }
        }

        if(!$silent) {
            Log::debug(get_class($this).'::MarketplaceListingId('.$listing->id.') - bbs: '.$this->bbs
                .', predicted_price: '.$predicted_price, $listing->repricing_algorithm['params'] ?? []);
        }

        return $predicted_price;
    }

    protected function init(MarketplaceListing $listing)
    {
        parent::init($listing);
        $this->bbs_hours = $listing->repricing_algorithm['params']['bbs_hours'] ?? $this->bbs_hours;
        $this->bbs = $this->getBuyBoxShare($listing, $this->bbs_hours);
    }

    protected function buyBoxShareIsHigh()
    {
        return $this->bbs > 0.6;
    }

    protected function buyBoxShareIsLow()
    {
        return $this->bbs < 0.3;
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
