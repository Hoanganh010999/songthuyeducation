<?php

use Illuminate\Support\Facades\Route;

// SPA - Catch all routes and return Vue app
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
