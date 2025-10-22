<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ImageController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\VariantController;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->name('login'); // Named route
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

    Route::get('catalog/products', [ProductController::class, 'index']);
    Route::get('catalog/products/{slug}', [ProductController::class, 'showproduct']);
    Route::get('catalog/categories', [CategoryController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart', [CartController::class, 'add']);
        Route::put('cart/{itemId}', [CartController::class, 'update']);
        Route::delete('cart/{itemId}', [CartController::class, 'remove']);

        Route::post('checkout', [CheckoutController::class, 'checkout']);
        Route::post('payment/verify', [CheckoutController::class, 'verifyPayment']);

        Route::get('orders', [OrderController::class, 'customerIndex']);
        Route::get('orders/{order}', [OrderController::class, 'customerShow']);
        Route::get('orders/{order}/invoice', [InvoiceController::class, 'download']);
    });

    Route::middleware(['auth:sanctum', 'can:admin'])->group(function () {
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('products.variants', VariantController::class)->shallow();
        Route::apiResource('products.images', ImageController::class)->shallow()->only(['index', 'store', 'destroy', 'update']);
        Route::apiResource('coupons', CouponController::class);
        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update']);

        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::get('dashboard/sales-by-day', [DashboardController::class, 'salesByDay']);
        Route::get('dashboard/top-products', [DashboardController::class, 'topProducts']);
        Route::get('dashboard/low-stock', [DashboardController::class, 'lowStock']);
    });
});