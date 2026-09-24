<?php

use App\Http\Controllers\Api\ShippingController;
use Illuminate\Support\Facades\Route;

Route::prefix('shipping')->middleware('throttle:30,1')->group(function (): void {
    Route::get('/destinations', [ShippingController::class, 'destinations'])
        ->name('api.shipping.destinations');
    Route::post('/cost', [ShippingController::class, 'cost'])
        ->name('api.shipping.cost');
});
