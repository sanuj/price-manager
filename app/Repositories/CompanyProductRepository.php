<?php

namespace App\Repositories;

use App\Company;
use App\CompanyProduct;
use App\Contracts\Repositories\CompanyProductRepositoryContract;
use Illuminate\Validation\Rule;
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
        Validator::validate($attributes, [
            'sku' => [
                'bail',
                'required',
                'max:255',
                Rule::unique('company_products')->where('company_id', $company->getKey()),
            ],
            'name' => 'required|max:255',
        ]);

        $product = new CompanyProduct($attributes);

        $product->company()->associate($company);

        if (!$product->save()) {
            // Throw create error.
            abort(500);
        }

        return $product;
    }

    public function update(CompanyProduct $product, array $attributes): CompanyProduct
    {
        Validator::validate($attributes, array_only([
            'sku' => [
                'bail',
                'required',
                'max:255',
                Rule::unique('company_products')->where('company_id', $product->company_id)->ignore($product->getKey()),
            ],
            'name' => 'required|max:255',
        ], array_keys($attributes)));

        if (!$product->update($attributes)) {
            // Throw update error.
            abort(500);
        }

        return $product;
    }
}
