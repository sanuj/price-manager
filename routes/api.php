<?php

$restful = ['only' => ['index', 'store', 'update', 'destroy']];

Route::group(['middleware' => 'auth:api,web'], function () use ($restful) {
    Route::resource('company/products', 'API\CompanyProductController', $restful);
    Route::resource('company/marketplaces', 'API\MarketplaceController', $restful);
});
