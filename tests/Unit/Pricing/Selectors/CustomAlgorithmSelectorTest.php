<?php

namespace Tests\Unit\Jobs;

use App\MarketplaceListing;
use App\Pricing\Algorithms\BuyBoxShareHeuristicPrice;
use App\Pricing\Algorithms\UserDefinedPrice;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CustomAlgorithmSelectorTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_select_algorithms()
    {
        // UserDefinedPrice
        $algorithm = $this->getAlgorithm('UserDefinedPrice');
        $this->assertContains(class_basename(get_class($algorithm)), UserDefinedPrice::class);

        // BuyBoxShareHeuristicPrice
        $algorithm = $this->getAlgorithm('BuyBoxShareHeuristicPrice');
        $this->assertContains(class_basename(get_class($algorithm)), BuyBoxShareHeuristicPrice::class);

    }

    public function test_it_can_throw_exception_on_invalid_algorithms()
    {
        $this->expectException(\ReflectionException::class);
        $this->getAlgorithm('foobar');
    }

    public function getAlgorithm($algorithm) {
        return resolve(config('pricing.selectors.custom'))
            ->algorithm(factory(MarketplaceListing::class, 1)->make([
                'repricing_algorithm' => compact('algorithm')
            ])->first());
    }

}
