<?php namespace App\Contracts;

use App\Marketplace;

interface MarketplaceDriverContract
{
    public function setPrice(string $id, float $price, array $options = []);

    public function use (Marketplace $marketplace): self;
}
