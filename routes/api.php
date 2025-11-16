<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        
        // Products
        Route::apiResource('products', ProductController::class);
        Route::post('products/import/csv', [ProductController::class, 'importCsv'])
            ->middleware('check.role:admin,vendor');

        // Orders
        Route::apiResource('orders', OrderController::class);
        Route::patch('orders/{id}/confirm', [OrderController::class, 'confirm'])
            ->middleware('check.role:admin');
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])
            ->middleware('check.role:admin');
    });
});

// Health check
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
    ]);
});
