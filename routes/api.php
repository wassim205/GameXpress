<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;

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
        Route::get('admin/dashboard', [DashboardController::class, 'index']);
        Route::get('admin/products', [ProductController::class, 'index']);
        Route::get('admin/products/{id}', [ProductController::class, 'show']);
        Route::post('admin/products', [ProductController::class, 'store']);
        Route::put('admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('admin/products/{id}', [ProductController::class, 'destroy']);
        Route::get('admin/categories', [CategoryController::class, 'index']);
        Route::get('admin/categories/{id}', [CategoryController::class, 'show']);
        Route::post('admin/categories', [CategoryController::class, 'store']);
        Route::put('admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('admin/categories/{id}', [CategoryController::class, 'destroy']);
        
    });
});


