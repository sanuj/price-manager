<?php

namespace Tests\Unit\Pricing\Algorithms;

use App\Exceptions\NoSnapshotsAvailableException;
use App\Exceptions\NoSnapshotsWithOffersException;
use App\MarketplaceListing;
use App\Mongo\Snapshot;
use App\Pricing\Algorithms\BuyBoxShareHeuristicPrice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BuyBoxShareHeuristicPriceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_calculate_buy_box_share() {
        $marketplace_listing = $this->getMarketplaceListing();
        $buy_box_share = $this->algorithm(true, $this->getSnapshots($marketplace_listing->id))
            ->getBuyBoxShare($marketplace_listing, 3);
        $this->assertLessThanOrEqual(1, $buy_box_share);
        $this->assertGreaterThanOrEqual(0, $buy_box_share);
    }

    public function test_it_can_handle_zero_snapshots() {
        $this->expectException(NoSnapshotsAvailableException::class);
        $marketplace_listing = $this->getMarketplaceListing();
        $this->algorithm(true, $this->getSnapshots($marketplace_listing->id, [], [], 0))
            ->getBuyBoxShare($marketplace_listing, 3);
    }

    public function test_it_can_handle_snapshots_without_any_offers() {
        $this->expectException(NoSnapshotsWithOffersException::class);
        $marketplace_listing = $this->getMarketplaceListing();
        $this->algorithm(true, $this->getSnapshots($marketplace_listing->id, []))
            ->getBuyBoxShare($marketplace_listing, 3);
    }

    public function test_it_can_increment_price() {
        $selling_price = 800;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(100, $selling_price, 750, 900));
        $this->assertEquals((2*0.05+1)*$selling_price, $predicted_price);
    }

    public function test_it_can_increment_price_with_custom_params() {
        $selling_price = 800;
        $multiplier = 4;
        $increment_factor = 0.01;
        $bbs_hours = 3;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(100, $selling_price, 750, 900, [
            'params' => compact('increment_factor', 'multiplier', 'bbs_hours')
        ]));
        $this->assertEquals(($multiplier*$increment_factor+1)*$selling_price, $predicted_price);
    }

    public function test_it_can_increment_price_for_medium_bbs() {
        $selling_price = 800;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(50, $selling_price, 750, 900));
        $this->assertEquals((0.05+1)*$selling_price, $predicted_price);
    }

    public function test_it_can_ceil_price() {
        $selling_price = 800;
        $max_price = 850;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(100, $selling_price, 750, $max_price));
        $this->assertLessThanOrEqual((2*0.05+1)*$selling_price, $max_price);
        $this->assertEquals($max_price, $predicted_price);
    }

    public function test_it_can_decrement_price() {
        $selling_price = 800;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(1, $selling_price, 750, 900));
        $this->assertEquals((1-0.05)*$selling_price, $predicted_price);
    }

    public function test_it_can_decrement_price_with_custom_params() {
        $selling_price = 800;
        $decrement_factor = 0.01;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(1, $selling_price, 750, 900, [
            'params' => compact('decrement_factor')
        ]));
        $this->assertEquals((1-$decrement_factor)*$selling_price, $predicted_price);
    }

    public function test_it_can_floor_price() {
        $selling_price = 800;
        $min_price = 750;
        $decrement_factor = 0.1;
        $predicted_price = $this->algorithm()->predict($this->getMarketplaceListing(1, $selling_price, $min_price, 850, [
            'params' => compact('decrement_factor')
        ]));
        $this->assertGreaterThanOrEqual((1-$decrement_factor)*$selling_price, $min_price);
        $this->assertEquals($min_price, $predicted_price);
    }

    public function algorithm($mock_buy_box_snapshots = false, $snapshots = null) {
        return $mock_buy_box_snapshots ?
            $this->mockMethod('buyBoxSnapshots', $snapshots ?? $this->getSnapshots())
            : $this->mockMethod('getBuyBoxShare', function (MarketplaceListing $marketplace_listing, $bbs_hours) {
                switch($marketplace_listing->id*$bbs_hours) {
                    case 1: return 0.1;
                    case 100: return 0.8;
                    case 300: return 0.8;
                    default: return 0.5;
                }
            });
    }

    public function mockMethod($method_name, $return_value) {
        $algorithm = $this->getMockBuilder(BuyBoxShareHeuristicPrice::class)
            ->setMethods([$method_name])->getMock();
        $algorithm->method($method_name)
            ->will(is_callable($return_value) ? $this->returnCallback($return_value) : $this->returnValue($return_value));
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

    public function getMarketplaceListing($id=null, $marketplace_selling_price=null, $marketplace_min_price=null,
                                          $marketplace_max_price=null, $repricing_algorithm=null) : MarketplaceListing {
        $params = [];
        if(!is_null($id))
            $params = array_merge($params, compact('id'));
        if(!is_null($marketplace_selling_price))
            $params = array_merge($params, compact('marketplace_selling_price'));
        if(!is_null($marketplace_min_price))
            $params = array_merge($params, compact('marketplace_min_price'));
        if(!is_null($marketplace_max_price))
            $params = array_merge($params, compact('marketplace_max_price'));
        if(!is_null($repricing_algorithm))
            $params = array_merge($params, compact('repricing_algorithm'));

        return factory(MarketplaceListing::class)->make($params);
    }

}
