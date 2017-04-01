<?php

namespace App\Http\Controllers\API;

use App\CompanyProduct;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CompanyProductController extends Controller
{
    public function index(Request $request)
    {
        return CompanyProduct::with($this->eagerLoading())->get();
    }
}
