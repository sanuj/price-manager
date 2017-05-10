<?php

namespace Tests\Unit\Pricing\Algorithms;

use App\Exceptions\NoSnapshotsAvailableException;
use App\Exceptions\NoSnapshotsWithOffersException;
use App\Mongo\Snapshot;
use App\Pricing\Algorithms\BuyBoxShareHeuristicPrice;
use Tests\TestCase;

class BuyBoxShareHeuristicPriceTest extends TestCase
{

    public function test_it_can_calculate_buy_box_share() {
        $marketplace_listing_id = rand(1, 100);
        $algorithm = $this->algorithm(true, $this->getSnapshots($marketplace_listing_id));
        $buy_box_share = $algorithm->getBuyBoxShare($marketplace_listing_id);
        $this->assertLessThanOrEqual(1, $buy_box_share);
        $this->assertGreaterThanOrEqual(0, $buy_box_share);
    }

    public function test_it_can_handle_zero_snapshots() {
        $marketplace_listing_id = rand(1, 100);
        $algorithm = $this->algorithm(true, $this->getSnapshots($marketplace_listing_id, [], [], 0));
        $this->expectException(NoSnapshotsAvailableException::class);
        $buy_box_share = $algorithm->getBuyBoxShare($marketplace_listing_id);
        echo 'Buy box share: ' . $buy_box_share . PHP_EOL;
    }

    public function test_it_can_handle_snapshots_without_any_offers() {
        $marketplace_listing_id = rand(1, 100);
        $algorithm = $this->algorithm(true, $this->getSnapshots($marketplace_listing_id, []));
        $this->expectException(NoSnapshotsWithOffersException::class);
        $algorithm->getBuyBoxShare($marketplace_listing_id);
    }

    public function algorithm($mock_buy_box_snapshots = false, $snapshots = null) {
        return $mock_buy_box_snapshots ?
            $this->mockMethod('buyBoxSnapshots', $snapshots ?? $this->getSnapshots())
            : $this->mockMethod('getBuyBoxShare', [[1, 50, 100], [0.1, 0.5, 0.8]]);
    }

    public function mockMethod($method_name, $return_value) {
        $algorithm = $this->getMockBuilder(BuyBoxShareHeuristicPrice::class);
        $algorithm = $algorithm->setMethods([$method_name])->getMock();
        $algorithm->method($method_name)
            ->willReturn($return_value);
        return $algorithm;
    }

    public function getSnapshots($marketplace_listing_id=null, $offers=null, $competitors=null, $count=20) {
        $params = [];
        if(!is_null($marketplace_listing_id))
            $params = array_merge($params, compact('marketplace_listing_id'));
        if(!is_null($offers))
            $params = array_merge($params, compact('offers'));
        if(!is_null($competitors))
            $params = array_merge($params, compact('competitors'));

        return factory(Snapshot::class, $count)->make($params);
    }

}
