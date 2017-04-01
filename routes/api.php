<?php

$restful = ['only' => ['index', 'store', 'update', 'destroy']];

Route::resource('products', 'API\CompanyProductController', $restful);
