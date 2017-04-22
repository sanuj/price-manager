<?php

namespace Tests\Unit\Drivers\Marketplace;

use App\Drivers\Marketplace\AmazonIndiaDriver;
use App\Marketplace\ProductOffer;
use CaponicaAmazonMwsComplete\AmazonClient\MwsProductClient;
use Tests\TestCase;

class AmazonIndiaDriverTest extends TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmazonIndiaDriver
     */
    public function driver()
    {
        $driver = $this->getMockBuilder(AmazonIndiaDriver::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getProductClient'])
                       ->getMock();
        $client = $this->getMockBuilder(MwsProductClient::class)
                       ->disableOriginalConstructor()
                       ->setMethods([
                           'getLowestPricedOffersForASIN',
                           'getLowestOfferListingsForASIN',
                           'getCompetitivePricingForASIN',
                           'getMyPriceForASIN',
                       ])
                       ->getMock();
        $client->method('getLowestPricedOffersForASIN')->willReturn(new XMLAble(file_get_contents(__DIR__.'/stubs/GetLowestPricedOffersForASIN.xml')));
        $client->method('getLowestOfferListingsForASIN')->willReturn(new XMLAble(file_get_contents(__DIR__.'/stubs/GetLowestOfferListingsForASIN.xml')));
        $client->method('getCompetitivePricingForASIN')->willReturn(new XMLAble(file_get_contents(__DIR__.'/stubs/GetCompetitivePricingForASIN.xml')));
        $client->method('getMyPriceForASIN')->willReturn(new XMLAble(file_get_contents(__DIR__.'/stubs/GetMyPriceForASIN.xml')));
        $driver->method('getProductClient')->willReturn($client);

        return $driver;
    }

    public function test_getPriceWithPricedOffersAPI()
    {
        $amazon = $this->driver();

        $result = $amazon->getPriceWithPricedOffersAPI(['B00S6JCFB4']);

        $this->assertIt($result);
    }

    public function test_getPriceWithOfferListingAPI()
    {
        $amazon = $this->driver();

        $result = $amazon->getPriceWithOfferListingAPI(['B00S6JCFB4']);

        $this->assertIt($result);
    }

    public function test_getPrice()
    {
        $amazon = $this->driver();

        $result = $amazon->getPrice('B00S6JCFB4');

        $this->assertArraySubset([
            'is_fulfilled' => true,
            'price' => 905.0,
            'currency' => 'INR',
            'has_buy_box' => true,
        ], $result['B00S6JCFB4'][0]);
    }

    /**
     * @param $result
     */
    protected function assertIt($result)
    {
        $this->assertArrayHasKey('B00S6JCFB4', $result);

        $items = $result['B00S6JCFB4'];
        foreach ($items as $item) {
            $this->assertInstanceOf(ProductOffer::class, $item);
        }

        $items = array_map(function (ProductOffer $item) {
            return $item->toArray();
        }, $items);

        $expected = [
            "is_fulfilled" => false,
            "price" => 905.0,
            "reviews" => 142,
            "currency" => "INR",
        ];

        $this->assertArraySubset($expected, $items[0]);
    }
}
