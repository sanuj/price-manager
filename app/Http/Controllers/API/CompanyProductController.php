<?php

namespace App\Http\Controllers\API;

use App\CompanyProduct;
use App\Contracts\Repositories\CompanyProductRepositoryContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyProductController extends Controller
{
    /**
     * @var CompanyProductRepositoryContract
     */
    protected $repository;


    /**
     * CompanyProductController constructor.
     *
     * @param CompanyProductRepositoryContract $repository
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

    public function destroy(CompanyProduct $product)
    {
        $this->authorize('delete', $product);

        if (!$product->delete()) {
            abort(500);
        }

        return $this->accepted();
    }
}
