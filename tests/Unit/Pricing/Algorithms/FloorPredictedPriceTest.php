<?php

namespace Tests\Unit\Pricing\Algorithms;

use App\MarketplaceListing;
use App\Mongo\Snapshot;
use App\Pricing\Algorithms\FloorPredictedPrice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FloorPredictedPriceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_compute_average() {
        $competitors = [["price" => 874.0],["price" => 949.0],["price" => 1039.0],["price" => 1149.0],
            ["price" => 1199.0],["price" => 1209.0]];
        $average_price = $this->mockMethod('getLastSnapshot', $this->getSnapshots($competitors))
                            ->competitorPrices($this->getMarketplaceListing());
        $this->assertEquals(round(collect($competitors)->average('price')), $average_price);
    }

    public function test_it_can_compute_median() {
        $competitors = [["price" => 874.0],["price" => 949.0],["price" => 1039.0],["price" => 1149.0],
            ["price" => 1199.0],["price" => 1209.0]];
        $average_price = $this->mockMethod('getLastSnapshot', $this->getSnapshots($competitors))
            ->competitorPrices($this->getMarketplaceListing(), 'median');
        $this->assertEquals(round(collect($competitors)->median('price')), $average_price);
    }

    public function test_it_can_predict_price() {
        $this->makeTest(1000, 900, 1200, 'average', 1079, 1079);
        $this->makeTest(1100, 900, 1200, 'median', 1079, 1100);
        $this->makeTest(1100, 1200, 1300, 'median', 1079, 1200);
        $this->makeTest(1100, 900, 1000, 'median', 1079, 1000);
    }

    public function makeTest($predicted_price, $min_price, $max_price, $floor_by, $competitor_price, $expected_price) {
        $listing = $this->getMarketplaceListing($min_price, $max_price, ['params' => compact('floor_by')]);
        $price = $this->mockMethod('competitorPrices', $competitor_price, $predicted_price)
            ->predict($listing, true);
        $this->assertEquals($expected_price, $price);
        $this->assertGreaterThanOrEqual($min_price, $price);
        $this->assertLessThanOrEqual($max_price, $price);
    }

    public function mockMethod($method_name, $return_value, $predicted_price_by_algo=0) {
        $algorithm = $this->getMockBuilder(FloorPredictedPrice::class)
            ->setMethods([$method_name, 'calculatePriceByAlgo'])->getMock();
        $algorithm->method($method_name)
            ->will(is_callable($return_value) ? $this->returnCallback($return_value) : $this->returnValue($return_value));
        $algorithm->method('calculatePriceByAlgo')->willReturn($predicted_price_by_algo);
        return $algorithm;
    }

    public function getSnapshots($competitors) {
        return factory(Snapshot::class)->make(compact('competitors'));
    }

    public function getMarketplaceListing($marketplace_min_price=null, $marketplace_max_price=null,
                                          $repricing_algorithm=null) : MarketplaceListing {
        $params = [];
        if(!is_null($marketplace_min_price))
            $params = array_merge($params, compact('marketplace_min_price'));
        if(!is_null($marketplace_max_price))
            $params = array_merge($params, compact('marketplace_max_price'));
        if(!is_null($repricing_algorithm))
            $params = array_merge($params, compact('repricing_algorithm'));

        return factory(MarketplaceListing::class)->make($params);
    }

}
