<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Profile\AddressController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [ProfileController::class, 'show']);
Route::put('/me', [ProfileController::class, 'update']);
Route::delete('/me', [ProfileController::class, 'destroy']);

Route::apiResource('addresses', AddressController::class)->except(['show']);

Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'storeItem']);
Route::put('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroyItem']);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

Route::post('/orders/{order}/payments/bankily/initiate', [PaymentController::class, 'initiateBankily']);
Route::post('/orders/{order}/payment-proof', [PaymentController::class, 'submitProof']);
