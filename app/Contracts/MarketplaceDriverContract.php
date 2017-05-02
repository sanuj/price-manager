<?php namespace App\Contracts;

use App\CompanyMarketplace;
use Illuminate\Support\Collection;

interface MarketplaceDriverContract
{
    /**
     * Get price & meta from marketplace API for owner's listing.
     *
     * @param string|array $asin
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getPrice($asin);

    /**
     * @param \App\Contracts\Collection|\App\MarketplaceListing[] $asin
     *
     * @return void
     *
     * TODO: Figure it out. (Return Value)
     */
    public function setPrice(Collection $asin);

    /**
     * Get price & meta from marketplace API.
     *
     * @param string|array $asin
     *
     * @return \App\Marketplace\ProductOffer[][]
     */
    public function getOffers($asin);

    public function use (CompanyMarketplace $marketplace, array $credentials = []): self;
}
