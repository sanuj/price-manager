<?php

namespace App\Repositories;

use App\Company;
use App\CompanyMarketplace;
use App\Contracts\Repositories\MarketplaceRepositoryContract;
use App\Marketplace;
use Illuminate\Validation\Rule;
use Transformer;
use Validator;

class MarketplaceRepository implements MarketplaceRepositoryContract
{

    /**
     * List of marketplace.
     *
     * @param \App\Company $company
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function listForCompany(Company $company)
    {
        return $company->with(array_map(function ($any) {
            return 'marketplaces.'.$any;
        }, Transformer::relations()))
                       ->marketplaces()
                       ->paginate();
    }

    /**
     * Create a marketplace record.
     *
     * @param \App\Company $company
     * @param array $attributes
     *
     * @return \App\CompanyMarketplace
     */
    public function createForCompany(Company $company, array $attributes): CompanyMarketplace
    {
        Validator::validate($attributes, [
            'marketplace_id' => ['bail', 'required', Rule::exists('marketplaces', 'id')],
        ]);

        $marketplace = Marketplace::find($attributes['marketplace_id']);

        /** @var \App\CompanyMarketplace $pivot */
        $pivot = $company->marketplaces()->newPivot($attributes);

        $pivot->company()->associate($company);
        $pivot->marketplace()->associate($marketplace);

        if (!$pivot->save()) {
            abort(500);
        }

        return $pivot;
    }

    /**
     * Create a marketplace record.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     * @param array $attributes
     *
     * @return \App\CompanyMarketplace
     */
    public function updateForCompany(Company $company, Marketplace $marketplace, array $attributes): CompanyMarketplace
    {
        $pivot = CompanyMarketplace::whereCompanyId($company->getKey())
                                   ->whereMarketplaceId($marketplace->getKey())
                                   ->firstOrFail();

        $pivot->fill($attributes);

        if (!$pivot->save()) {
            abort(500);
        }

        return $pivot;
    }

    /**
     * Delete a marketplace record.
     *
     * @param \App\Company $company
     * @param \App\Marketplace $marketplace
     *
     * @return void
     */
    public function deleteForCompany(Company $company, Marketplace $marketplace)
    {
        $pivot = CompanyMarketplace::whereCompanyId($company->getKey())
                                   ->whereMarketplaceId($marketplace->getKey())
                                   ->firstOrFail();

        if (!$pivot->delete()) {
            abort(500);
        }
    }
}
