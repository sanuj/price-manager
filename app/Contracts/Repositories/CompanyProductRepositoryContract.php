<?php namespace App\Contracts\Repositories;

use App\Company;
use App\CompanyProduct;

interface CompanyProductRepositoryContract
{
    /**
     * @param \App\Company $company
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function listForCompany(Company $company);

    /**
     * @param \App\Company $company
     * @param array $attributes
     *
     * @return \App\CompanyProduct
     */
    public function createForCompany(Company $company, array $attributes): CompanyProduct;

    /**
     * @param \App\CompanyProduct $product
     * @param array $attributes
     *
     * @return \App\CompanyProduct
     */
    public function update(CompanyProduct $product, array $attributes): CompanyProduct;
}
