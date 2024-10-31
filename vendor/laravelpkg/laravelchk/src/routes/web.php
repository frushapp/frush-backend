<?php

/*Route::group(['namespace']);*/

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Laravelpkg\Laravelchk\Http\Controllers', 'middleware' => ['web']], function () {
    Route::any('dmvf', 'LaravelchkController@dmvf')->name('dmvf');
    Route::get('activation-check', 'LaravelchkController@actch')->name('activation-check');
});
