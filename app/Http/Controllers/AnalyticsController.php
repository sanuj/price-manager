<?php

namespace App\Http\Controllers;

use App\Mongo\Snapshot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{

    public function snapshots(Request $request) {
//        $this->authorize('read', Snapshot::class);

        $snapshots = Snapshot::where('marketplace_listing_id', (int)$request->marketplace_listing_id)
            ->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $request->start_date))
            ->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $request->end_date))
            ->get();

        $snapshots = $snapshots->map(function($snap) {
            $snap->created_at = $snap->created_at->toDateString();
            return $snap;
        });
        return collect($snapshots->toArray())->groupBy('created_at')->toJson();
    }
}
