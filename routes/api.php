<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V2\CartController;
use App\Models\CartItem;

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });

Route::prefix('v1')->group(function () {
    Route::post('admin/register', [AuthController::class, 'register']);
    Route::post('admin/login', [AuthController::class, 'login']);
    // Route::get('admin/permissions', [ProductController::class, 'permissions']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('admin/logout', [AuthController::class, 'logout']);
        Route::middleware('role:super_admin')->group(function () {
            Route::get('admin/dashboard', [DashboardController::class, 'index']);
        });
        // Products Routes
        Route::get('admin/products', [ProductController::class, 'index']);
        Route::get('admin/products/{id}', [ProductController::class, 'show']);
        Route::post('admin/products', [ProductController::class, 'store']);
        Route::put('admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('admin/products/{id}', [ProductController::class, 'destroy']);

        // Categories Routes
        Route::get('admin/categories', [CategoryController::class, 'index']);
        Route::get('admin/categories/{id}', [CategoryController::class, 'show']);
        Route::post('admin/categories', [CategoryController::class, 'store']);
        Route::put('admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('admin/categories/{id}', [CategoryController::class, 'destroy']);

        // Users Routes
        Route::get('admin/users', [UserController::class, 'index']);
        Route::get('admin/users/{id}', [UserController::class, 'show']);
        Route::post('admin/users', [UserController::class, 'store']);
        Route::put('admin/users/{id}', [UserController::class, 'update']);
        Route::delete('admin/users/{id}', [UserController::class, 'destroy']);
    });
});