<?php

namespace App\Repositories;

use App\CompanyProduct;
use App\Contracts\Repositories\MarketplaceListingRepositoryContract;
use App\Facades\Currency;
use App\Marketplace;
use App\MarketplaceListing;
use Illuminate\Validation\Rule;
use Transformer;
use Validator;

class MarketplaceListingRepository implements MarketplaceListingRepositoryContract
{

    /**
     * Marketplace listing for the product.
     *
     * @param \App\CompanyProduct $product
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function listForProduct(CompanyProduct $product)
    {
        return MarketplaceListing::with(Transformer::relations())
                                 ->whereCompanyProductId($product->getKey())
                                 ->paginate();
    }

    /**
     * List a product on a marketplace.
     *
     * @param \App\CompanyProduct $product
     * @param array $attributes
     *
     * @return \App\MarketplaceListing
     */
    public function createForProduct(CompanyProduct $product, array $attributes): MarketplaceListing
    {
        Validator::validate($attributes, [
            'marketplace_id' => [
                'bail',
                'required',
                Rule::exists('marketplaces', 'id'),
                Rule::unique('marketplace_listings')->where('company_product_id', $product->getKey()),
            ],
            'uid' => 'required|max:255', // ASIN for Amazon.
            'cost_price' => 'required|numeric',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'selling_price' => 'nullable|numeric',
        ]);

        $marketplace = Marketplace::find($attributes['marketplace_id']);

        $listing = new MarketplaceListing($attributes);

        $listing->marketplace()->associate($marketplace);
        $listing->companyProduct()->associate($product);

        $listing->sku = $listing->sku ?? $product->sku;
        $listing->selling_price = $listing->selling_price ?? $listing->max_price;

        $this->updatePrices($listing);


        if (!$listing->save()) {
            abort(500);
        }

        return $listing;
    }

    /**
     * Update a marketplace listing.
     *
     * @param \App\MarketplaceListing $listing
     * @param array $attributes
     *
     * @return \App\MarketplaceListing
     */
    public function update(MarketplaceListing $listing, array $attributes): MarketplaceListing
    {
        Validator::validate($attributes, [
            'uid' => 'nullable|max:255', // ASIN for Amazon.
            'cost_price' => 'nullable|numeric',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
        ]);

        $listing->fill($attributes);

        $this->updatePrices($listing);

        if (!$listing->save()) {
            abort(500);
        }

        return $listing;
    }

    protected function updatePrices(MarketplaceListing $listing)
    {
        // TODO: This may require currency conversion. Uncomment next line when required.
        // Currency::from('INR')->to($marketplace->currency);

        $listing->marketplace_cost_price = Currency::convert($listing->cost_price);
        $listing->marketplace_selling_price = Currency::convert($listing->selling_price);
        $listing->marketplace_min_price = Currency::convert($listing->min_price);
        $listing->marketplace_max_price = Currency::convert($listing->max_price);
    }
}
