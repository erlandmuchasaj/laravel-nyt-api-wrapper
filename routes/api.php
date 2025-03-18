<?php

use App\Http\Controllers\Api\V1\BestSellerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('v1.')
    ->middleware(['idempotent', 'gzip', 'throttle:api'])
    ->group(function () {
        Route::get('/bestsellers', BestSellerController::class)->name('bestsellers');
    });

//  /**
//   * Version 2 of the API
//   * In the future we can update the API version to v2
//   */
//  Route::prefix('v2')
//       ->name('v2.')
//       ->namespace('App\Http\Controllers\Api\V2')
//       ->middleware(['idempotent', 'gzip', 'throttle:api'])
//       ->group(function () {
//           # Add new routes here
//       });
