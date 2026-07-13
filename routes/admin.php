<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind('customer', fn (string $value) => User::query()
    ->where('type', 'customer')
    ->whereKey($value)
    ->firstOrFail());

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
Route::resource('products', ProductController::class);

Route::resource('categories', CategoryController::class)->except(['show']);

Route::resource('collections', CollectionController::class)->except(['show']);
Route::post('collections/{collection}/sync-products', [CollectionController::class, 'syncProducts'])
    ->name('collections.sync-products');

Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
Route::post('orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.add-note');
Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
Route::patch('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])
    ->name('customers.deactivate');

Route::resource('discounts', DiscountController::class)->except(['show']);

Route::resource('pages', PageController::class)->except(['show']);

Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
Route::post('shipping/zones', [ShippingController::class, 'storeZone'])->name('shipping.zones.store');
Route::post('shipping/rates', [ShippingController::class, 'storeRate'])->name('shipping.rates.store');
Route::patch('shipping/zones/{zone}', [ShippingController::class, 'updateZone'])->name('shipping.zones.update');
Route::patch('shipping/rates/{rate}', [ShippingController::class, 'updateRate'])->name('shipping.rates.update');
Route::delete('shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('shipping.zones.destroy');
Route::delete('shipping/rates/{rate}', [ShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');

Route::get('settings', [SettingController::class, 'edit'])
    ->middleware('role:owner')
    ->name('settings.edit');
Route::put('settings', [SettingController::class, 'update'])
    ->middleware('role:owner')
    ->name('settings.update');

Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
Route::patch('payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])
    ->middleware('role:owner')
    ->name('payment-methods.toggle');
Route::patch('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'updateConfig'])
    ->middleware('role:owner')
    ->name('payment-methods.update');

Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
