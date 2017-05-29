<?php

namespace App\Http\Controllers;

use App\MarketplaceListing;
use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{

    public function snapshots(Request $request) {
        $this->authorize('read', Snapshot::class);
        $marketplace_listing = $this->getMarketplaceListing($request->sku_uid_id, $request->user()->company->getKey());
        $snapshots = Snapshot::where('marketplace_listing_id', $marketplace_listing->getKey())
            ->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $request->start_date))
            ->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $request->end_date))
            ->get();

        $snapshots = $snapshots->map(function($snap) {
            $snap->created_at = $snap->created_at->toDateString();
            return $snap;
        });
        return collect($snapshots->toArray())->groupBy('created_at')->toJson();
    }

    protected function getMarketplaceListing($sku_uid_id, $company_id) {
        return MarketplaceListing::where(['sku' => $sku_uid_id, 'company_id' => $company_id])
                                ->orWhere(['uid' => $sku_uid_id, 'company_id' => $company_id])
                                ->orWhere('id', (int)$sku_uid_id)
                                ->first();
    }
}
