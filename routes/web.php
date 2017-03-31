<?php

Auth::routes();
Route::get('/login/services/easyecom', 'Auth\LoginController@loginWithEasyECom');

Route::get('/', 'HomeController@index');

// NOTE: **This should be last in list. It captures everything.**
Route::group(['middleware' => 'auth'], function () {
    Route::get('/{anything}', 'DashboardController@index')
         ->where('anything', '^(?!(api|attach|app|icon|img)).*$');
});

