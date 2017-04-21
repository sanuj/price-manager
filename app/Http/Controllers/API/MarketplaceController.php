<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\MarketplaceRepositoryContract;
use App\Marketplace;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MarketplaceController extends Controller
{
    /**
     * @var \App\Contracts\Repositories\MarketplaceRepositoryContract
     */
    protected $repository;

    /**
     * MarketplaceController constructor.
     *
     * @param \App\Contracts\Repositories\MarketplaceRepositoryContract $repository
     */
    public function __construct(MarketplaceRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $this->authorize('read', Marketplace::class);

        return $this->repository->listForCompany($request->user()->company);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Marketplace::class);

        return $this->repository->createForCompany($request->user()->company, $request->input());
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $this->authorize('update', $marketplace);

        return $this->repository->updateForCompany($request->user()->company, $marketplace, $request->input());
    }

    public function destroy(Request $request, Marketplace $marketplace)
    {
        $this->authorize('delete', $marketplace);

        $this->repository->deleteForCompany($request->user()->company, $marketplace);

        return $this->accepted();
    }
}
