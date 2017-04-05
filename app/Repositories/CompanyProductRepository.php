<?php

namespace App\Repositories;

use App\Company;
use App\CompanyProduct;
use App\Contracts\Repositories\CompanyProductRepositoryContract;
use Request as Input;
use Transformer;
use Validator;

class CompanyProductRepository implements CompanyProductRepositoryContract
{
    public function listForCompany(Company $company)
    {
        return CompanyProduct::with(Transformer::relations())
                             ->whereCompanyId($company->getKey())
                             ->paginate()
                             ->appends(Input::query());
    }

    public function createForCompany(Company $company, array $attributes): CompanyProduct
    {
        if (Validator::make($attributes, [])->fails()) {
            // Throw validation error!
        }

        $product = new CompanyProduct($attributes);

        $product->company()->associate($company);

        if (!$product->save()) {
            // Throw create error.
        }

        return $product;
    }

    public function update(CompanyProduct $product, array $attributes): CompanyProduct
    {
        if (Validator::make($attributes, [])->fails()) {
            // Throw validation error.
        }

        if (!$product->update($attributes)) {
            // Throw update error.
        }

        return $product;
    }
}
