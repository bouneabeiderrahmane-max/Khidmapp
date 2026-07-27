<?php

use App\Http\Controllers\Profile\AddressController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [ProfileController::class, 'show']);
Route::put('/me', [ProfileController::class, 'update']);
Route::delete('/me', [ProfileController::class, 'destroy']);

Route::apiResource('addresses', AddressController::class)->except(['show']);
