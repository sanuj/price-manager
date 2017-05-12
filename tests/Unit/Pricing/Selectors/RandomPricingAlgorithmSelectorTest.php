<?php

namespace Tests\Unit\Jobs;

use App\MarketplaceListing;
use App\Pricing\Selectors\RandomPricingAlgorithmSelector;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RandomPricingAlgorithmSelectorTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_can_select_algorithms()
    {
        $algorithms = config('pricing.algorithms');
        $algorithm = resolve(RandomPricingAlgorithmSelector::class)
            ->algorithm(factory(MarketplaceListing::class, 1)->make()->first());
        $this->assertContains(class_basename(get_class($algorithm)), $algorithms);
    }

}
