<?php namespace App\Contracts;

use App\CompanyMarketplace;
use Illuminate\Support\Collection;

interface MarketplaceDriverContract
{
    /**
     * Rules to validate marketplace credentials.
     *
     * @return array
     */
    public function getCredentialRules(): array;
    /**
     * Get price & meta from marketplace API for owner's listing.
     *
     * @param Collection|\App\MarketplaceListing[] $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getPrice(Collection $listings);

    /**
     * @param \App\MarketplaceListing[]|\Illuminate\Support\Collection $listings
     *
     * @return void TODO: Figure it out. (Return Value)
     * TODO: Figure it out. (Return Value)
     */
    public function setPrice(Collection $listings);

    /**
     * Get price & meta from marketplace API.
     *
     * @param Collection|\App\MarketplaceListing[] $listings
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getOffers(Collection $listings);

    public function use (CompanyMarketplace $marketplace, array $credentials = []): self;
}
