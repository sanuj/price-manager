<?php

namespace App\Contracts\Repositories;

use App\CompanyProduct;
use App\MarketplaceListing;

interface MarketplaceListingRepositoryContract
{
    /**
     * Marketplace listing for the product.
     *
     * @param \App\CompanyProduct $product
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function listForProduct(CompanyProduct $product);

    /**
     * List a product on a marketplace.
     *
     * @param \App\CompanyProduct $product
     * @param array $attributes
     *
     * @return \App\MarketplaceListing
     */
    public function createForProduct(CompanyProduct $product, array $attributes): MarketplaceListing;

    /**
     * Update a marketplace listing.
     *
     * @param \App\MarketplaceListing $listing
     * @param array $attributes
     *
     * @return \App\MarketplaceListing
     */
    public function update(MarketplaceListing $listing, array $attributes): MarketplaceListing;
}
