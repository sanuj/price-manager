<?php

namespace App\Contracts\Repositories;

use App\Company;
use App\CompanyMarketplace;
use App\Marketplace;

interface MarketplaceRepositoryContract
{
    /**
     * List of marketplace.
     *
     * @param \App\Company $company
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function listForCompany(Company $company);

    /**
     * Create a marketplace record.
     *
     * @param \App\Company $company
     * @param array $attributes
     *
     * @return \App\CompanyMarketplace
     */
    public function createForCompany(Company $company, array $attributes): CompanyMarketplace;

    /**
     * Update a marketplace record.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     * @param array $attributes
     *
     * @return \App\CompanyMarketplace
     */
    public function updateForCompany(Company $company, Marketplace $marketplace, array $attributes): CompanyMarketplace;

    /**
     * Delete a marketplace record.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     *
     * @return void
     */
    public function deleteForCompany(Company $company, Marketplace $marketplace);
}
