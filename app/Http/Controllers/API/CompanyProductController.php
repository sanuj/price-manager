<?php

namespace App\Http\Controllers\API;

use App\Company;
use App\CompanyProduct;
use App\Contracts\Repositories\CompanyProductRepositoryContract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyProductController extends Controller
{
    /**
     * @var \App\Contracts\Repositories\CompanyProductRepositoryContract
     */
    protected $repository;


    /**
     * CompanyProductController constructor.
     *
     * @param \App\Contracts\Repositories\CompanyProductRepositoryContract $repository
     */
    public function __construct(CompanyProductRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $this->authorize('read', CompanyProduct::class);

        return $this->repository->listForCompany($request->user()->company);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CompanyProduct::class);

        return $this->repository->createForCompany($request->user()->company, $request->input());
    }

    public function update(Request $request, CompanyProduct $product)
    {
        $this->authorize('update', $product);

        return $this->repository->update($product, $request->input());
    }

    public function delete(CompanyProduct $product)
    {
        $this->authorize('delete', $product);

        if (!$product->delete()) {
            // Throw delete failed.
        }

        return $this->accepted();
    }
}
