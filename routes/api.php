<?php

$restful = ['only' => ['index', 'store', 'update', 'destroy']];

Route::group(['middleware' => 'auth:api,web', 'prefix' => '/company'], function () use ($restful) {
    Route::resource('products', 'API\CompanyProductController', $restful);
    Route::resource('products.listings', 'API\MarketplaceListingController', $restful);
    Route::resource('marketplaces', 'API\MarketplaceController', $restful);
});
