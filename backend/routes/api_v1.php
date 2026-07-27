<?php

use App\Http\Controllers\Admin\BoutiqueController as AdminBoutiqueController;
use App\Http\Controllers\Admin\BoutiqueSyncController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CategoryMappingController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BoutiqueController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'locale' => app()->getLocale(),
        'time' => now()->toIso8601String(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/otp/request', [AuthController::class, 'requestOtp'])->middleware('throttle:5,1');
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'registerEmail'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'loginEmail'])->middleware('throttle:10,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:api')->group(base_path('routes/api_v1_me.php'));

Route::get('/boutiques', [BoutiqueController::class, 'index']);
Route::get('/boutiques/{boutique:slug}', [BoutiqueController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);

Route::middleware(['auth:api', 'can:boutiques.manage'])->prefix('admin')->group(function () {
    Route::apiResource('boutiques', AdminBoutiqueController::class);
    Route::patch('/boutiques/{boutique}/status', [AdminBoutiqueController::class, 'updateStatus']);
});

Route::middleware(['auth:api', 'can:catalog.manage'])->prefix('admin')->group(function () {
    Route::apiResource('categories', AdminCategoryController::class)->except(['show']);

    Route::get('/boutiques/{boutique}/category-mappings', [CategoryMappingController::class, 'index']);
    Route::put('/boutiques/{boutique}/category-mappings/{categoryMapping}', [CategoryMappingController::class, 'update']);

    Route::post('/boutiques/{boutique}/sync', [BoutiqueSyncController::class, 'trigger']);
    Route::get('/boutiques/{boutique}/sync-logs', [BoutiqueSyncController::class, 'logs']);

    Route::get('/products', [AdminProductController::class, 'index']);
    Route::get('/products/{product}', [AdminProductController::class, 'show']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
});
