<?php

namespace App\Drivers\Marketplace;

use App\Contracts\MarketplaceDriverContract;
use App\Marketplace;
use CaponicaAmazonMwsComplete\MwsProductClient;

class AmazonIndiaDriver implements MarketplaceDriverContract
{
    /**
     * @var \CaponicaAmazonMwsComplete\MwsProductClient
     */
    protected $client;

    /**
     * @var \App\Marketplace
     */
    protected $marketplace;

    /**
     * @var array
     */
    protected $credentials;

    /**
     * AmazonIndiaDriver constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->client = new MwsProductClient(
            $config['key'], $config['secret'], $config['name'], $config['version'],
            ['ServiceURL' => '']
        );
    }

    public function setPrice(string $id, float $price, array $options = [])
    {
        // TODO: Implement setPrice() method.
    }

    public function setPriceMultiple(array $payload)
    {
        // TODO: Implement bulk setPrice method.
    }

    public function use (Marketplace $marketplace, array $credentials = []): MarketplaceDriverContract
    {
        $this->marketplace = $marketplace;
        $this->credentials = $credentials;

        return $this;
    }
}
