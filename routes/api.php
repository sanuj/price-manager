<?php

$restful = ['only' => ['index', 'store', 'update', 'destroy']];

Route::group(['middleware' => 'auth:api,web'], function () use ($restful) {
    Route::resource('products', 'API\CompanyProductController', $restful);
    Route::resource('marketplaces', 'API\MarketplaceController', $restful);
});
