<?php

use App\Http\Controllers\Webhooks\EasypaisaWebhookController;
use App\Http\Controllers\Webhooks\JazzCashWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe', StripeWebhookController::class)->name('stripe');
Route::post('/jazzcash', JazzCashWebhookController::class)->name('jazzcash');
Route::get('/jazzcash/simulate/{payment}', [JazzCashWebhookController::class, 'simulate'])->name('jazzcash.simulate');
Route::post('/easypaisa', EasypaisaWebhookController::class)->name('easypaisa');
Route::get('/easypaisa/simulate/{payment}', [EasypaisaWebhookController::class, 'simulate'])->name('easypaisa.simulate');
