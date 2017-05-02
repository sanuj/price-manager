<?php namespace App\Contracts;

use App\CompanyMarketplace;

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

    public function setPrice($asin);

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
