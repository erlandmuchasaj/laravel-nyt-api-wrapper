<?php

use App\Http\Controllers\Api\V1\BestSellerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('v1.')
    ->middleware(['idempotent', 'gzip', 'throttle:api'])
    ->group(function () {

        Route::get('/bestsellers', BestSellerController::class)->name('bestsellers');
    });
