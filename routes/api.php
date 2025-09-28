<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Webhook\NowPaymentsWebhookController;

// =====================
// Auth
// =====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// =====================
// Routes for authenticated users
// =====================
Route::middleware('auth:sanctum')->group(function() {
    Route::post('/logout', [AuthController::class, 'logout']);

    // =====================
    // User routes
    // =====================
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/download', [OrderController::class, 'download']);

    // فایل دانلودی
    Route::get('/orders/{order}/file', [FileController::class, 'serve'])
        ->name('orders.file.download');

    // =====================
    // Admin-only routes
    // =====================
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        // می‌تونی ویرایش/حذف محصول رو هم اینجا اضافه کنی
    });
});

// =====================
// Webhook NOWPayments
// =====================
Route::post('/webhook/nowpayments', NowPaymentsWebhookController::class)
    ->name('webhook.nowpayments');
