<?php

namespace App\Pricing\Algorithms;

use App\Exceptions\NoSnapshotsAvailableException;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FloorPredictedPrice extends BasePrice
{

    public function calculatePrice(MarketplaceListing $listing, $silent=false): float
    {
        $this->init($listing);

        $floor_by = $listing->repricing_algorithm['params']['floor_by'] ?? null;
        $algorithm = $listing->repricing_algorithm['params']['algo'] ?? BuyBoxShareHeuristicPrice::class;

        $predicted_price = $this->calculatePriceByAlgo($listing, $algorithm);

        if($floor_by) {
            $floor_price = $this->competitorPrices($listing, $floor_by);
            $predicted_price = max($floor_price, $predicted_price);
        }

        if(!$silent) {
            Log::debug(get_class($this).'::MarketplaceListingId('.$listing->id.
                ') predicted_price: '.$predicted_price, $listing->repricing_algorithm['params'] ?? []);
        }

        return $predicted_price;
    }

    protected function calculatePriceByAlgo($listing, $algo) {
        return resolve($algo)->calculatePrice($listing);
    }

    public function competitorPrices(MarketplaceListing $marketplace_listing, $type=null) {
        $snapshot = $this->getLastSnapshot($marketplace_listing->getKey());

        if (is_null($snapshot)) {
            throw new NoSnapshotsAvailableException($marketplace_listing);
        }

        switch($type) {
            case 'median':
                return round(collect($snapshot['competitors'])->median('price'));
            default:
                return round(collect($snapshot['competitors'])->avg('price'));
        }
    }

    protected function getLastSnapshot($marketplace_listing_id) {
        return Snapshot::where('marketplace_listing_id', $marketplace_listing_id)
            ->where('created_at', '>', Carbon::now()->subHours(2))
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
