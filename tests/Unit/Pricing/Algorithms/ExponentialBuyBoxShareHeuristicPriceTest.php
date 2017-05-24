<?php

namespace Tests\Unit\Pricing\Algorithms;

use App\MarketplaceListing;
use App\Pricing\Algorithms\ExponentialBuyBoxShareHeuristicPrice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ExponentialBuyBoxShareHeuristicPriceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_works() {
        $selling_price = 800;
        $increment_base = 2;
        $increment_exponent = 1;
        $marketplace_listing = $this->getMarketplaceListing(100, $selling_price, 750, 900, [
            'params' => compact('increment_base', 'increment_exponent')
        ]);
        $predicted_price = $this->algorithm()->predict($marketplace_listing);
        $this->assertEquals($selling_price + pow($increment_base, $increment_exponent), $predicted_price);
        $this->assertEquals($increment_exponent+1, $marketplace_listing->repricing_algorithm['params']['increment_exponent']);
    }

    public function test_it_can_ceil_price() {
        $selling_price = 800;
        $max_price = 850;
        $increment_base = 100;
        $predicted_price = $this->algorithm()->predict(
            $this->getMarketplaceListing(100, $selling_price, 750, $max_price, [
                'params' => compact('increment_base')
            ]));
        $this->assertLessThanOrEqual($selling_price+$increment_base, $max_price);
        $this->assertEquals($max_price, $predicted_price);
    }

    public function test_it_can_floor_price() {
        $selling_price = 800;
        $min_price = 750;
        $decrement_base = 100;
        $predicted_price = $this->algorithm()->predict(
            $this->getMarketplaceListing(1, $selling_price, $min_price, 850, [
                'params' => compact('decrement_base')
            ]));
        $this->assertGreaterThanOrEqual($selling_price-$decrement_base, $min_price);
        $this->assertEquals($min_price, $predicted_price);
    }

    public function algorithm() {
        $algorithm = $this->getMockBuilder(ExponentialBuyBoxShareHeuristicPrice::class)
            ->setMethods(['getBuyBoxShare'])->getMock();
        $algorithm->method('getBuyBoxShare')
            ->will($this->returnCallback(function (MarketplaceListing $marketplace_listing, $bbs_hours) {
                switch($marketplace_listing->id*$bbs_hours) {
                    case 1: return 0.1;
                    case 100: return 0.8;
                    case 300: return 0.8;
                    default: return 0.5;
                }
            }));
        return $algorithm;
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
