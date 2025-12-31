<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', \App\Domains\Auth\Controllers\RegisterController::class);
Route::post('/auth/login', \App\Domains\Auth\Controllers\LoginController::class);
Route::post('/auth/verify-otp', [\App\Domains\Auth\Controllers\LoginController::class, 'verifyOtp']);
Route::post('/auth/google', [\App\Domains\Auth\Controllers\LoginController::class, 'googleLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [\App\Domains\Auth\Controllers\LogoutController::class, 'logout']);
    // Product Management
    Route::post('/products', [\App\Domains\Product\Controllers\ProductController::class, 'store']);
    Route::get('/products', [\App\Domains\Product\Controllers\ProductController::class, 'index']);
    Route::put('/products/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'update']);
    Route::delete('/products/{product}', [\App\Domains\Product\Controllers\ProductController::class, 'destroy']);
    Route::post('/products/{product}/image', [\App\Domains\Product\Controllers\ProductImageController::class, 'update']);
    Route::get('/products/scan/{sku}', [\App\Domains\Product\Controllers\ProductLookupController::class, 'scan']);

    // Supplier Management
    Route::post('/suppliers', [\App\Domains\Supplier\Controllers\SupplierController::class, 'store']);
    Route::get('/suppliers', [\App\Domains\Supplier\Controllers\SupplierController::class, 'index']);
    Route::put('/suppliers/{supplier}', [\App\Domains\Supplier\Controllers\SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [\App\Domains\Supplier\Controllers\SupplierController::class, 'destroy']);

    // Cashier Management (Admin Only)
    Route::apiResource('cashiers', \App\Domains\Auth\Controllers\CashierController::class);

    // Transactions & Inventory
    Route::post('/purchases', [\App\Domains\Purchase\Controllers\PurchaseController::class, 'store']);
    Route::get('/purchases', [\App\Domains\Purchase\Controllers\PurchaseController::class, 'index']);
    Route::get('/purchases/{purchase}', [\App\Domains\Purchase\Controllers\PurchaseController::class, 'show']);

    Route::post('/pos', [\App\Domains\Pos\Controllers\PosController::class, 'store']);
    Route::get('/pos', [\App\Domains\Pos\Controllers\PosController::class, 'index']);
    Route::get('/pos/{pos}', [\App\Domains\Pos\Controllers\PosController::class, 'show']);

    Route::post('/inventory/adjust', [\App\Domains\Inventory\Controllers\InventoryController::class, 'adjust']);
    Route::get('/inventory/adjust', [\App\Domains\Inventory\Controllers\InventoryController::class, 'historyAdjustments']);

    Route::post('/inventory/opname', [\App\Domains\Inventory\Controllers\InventoryController::class, 'opname']);
    Route::get('/inventory/opname', [\App\Domains\Inventory\Controllers\InventoryController::class, 'historyOpnames']);
    // Auth Features
    Route::get('/user', [\App\Domains\Auth\Controllers\ProfileController::class, 'show']);
    Route::put('/auth/profile', [\App\Domains\Auth\Controllers\ProfileController::class, 'update']);
    Route::put('/auth/password', [\App\Domains\Auth\Controllers\PasswordController::class, 'update']);
});

// Public Auth Routes (Forgot/Reset Password)
Route::post('/auth/forgot-password', [\App\Domains\Auth\Controllers\PasswordResetController::class, 'sendResetLink']);

Route::middleware(['auth:sanctum', 'can:admin-only'])
    ->get('/admin/ping', fn () => response()->json(['ok' => true]));
