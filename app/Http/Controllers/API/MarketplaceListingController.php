<?php

namespace App\Http\Controllers\API;

use App\CompanyProduct;
use App\Contracts\Repositories\MarketplaceListingRepositoryContract;
use App\Http\Controllers\Controller;
use App\MarketplaceListing;
use Illuminate\Http\Request;

class MarketplaceListingController extends Controller
{
    /**
     * @var MarketplaceListingRepositoryContract
     */
    protected $repository;


    /**
     * MarketplaceListingController constructor.
     *
     * @param MarketplaceListingRepositoryContract $repository
     */
    public function __construct(MarketplaceListingRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function index(CompanyProduct $product)
    {
        $this->authorize('read-listing', $product);

        return $this->repository->listForProduct($product);
    }

    public function store(Request $request, CompanyProduct $product)
    {
        $this->authorize('create-listing', $product);

        return $this->repository->createForProduct($product, $request->input());
    }

    public function update(Request $request, CompanyProduct $product, MarketplaceListing $listing)
    {
        $this->checkProductListing($product, $listing);

        $this->authorize('update-listing', $product);

        return $this->repository->update($listing, $request->input());
    }

    public function destroy(CompanyProduct $product, MarketplaceListing $listing)
    {
        $this->checkProductListing($product, $listing);

        $this->authorize('delete-listing', $product);

        if (!$listing->delete()) {
            abort(500);
        }

        return $this->accepted();
    }

    /**
     * Check is listing for product
     *
     * @param \App\CompanyProduct $product
     * @param \App\MarketplaceListing $listing
     */
    protected function checkProductListing(CompanyProduct $product, MarketplaceListing $listing)
    {
        if ($product->getKey() !== $listing->company_product_id) {
            abort(404);
        }
    }
}
