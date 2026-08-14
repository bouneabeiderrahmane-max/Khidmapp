<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Complaint\ComplaintAttachmentController;
use App\Http\Controllers\Complaint\ComplaintController;
use App\Http\Controllers\CustomOrderRequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Profile\AddressController;
use App\Http\Controllers\Profile\NotificationController;
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

Route::get('/custom-order-requests', [CustomOrderRequestController::class, 'index']);
Route::post('/custom-order-requests', [CustomOrderRequestController::class, 'store']);
Route::get('/custom-order-requests/{customOrderRequest}', [CustomOrderRequestController::class, 'show']);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::get('/notification-preferences', [NotificationController::class, 'showPreferences']);
Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']);

Route::get('/complaints', [ComplaintController::class, 'index']);
Route::post('/complaints', [ComplaintController::class, 'store']);
Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
Route::post('/complaints/{complaint}/messages', [ComplaintController::class, 'storeMessage']);

Route::get('/complaint-messages/{message}/attachments/{index}', [ComplaintAttachmentController::class, 'show'])
    ->name('complaint-messages.attachment');
