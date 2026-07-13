<?php

use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ReviewController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])
    ->middleware('throttle:30,1')
    ->name('search.autocomplete');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/address', [CheckoutController::class, 'updateAddress'])->name('checkout.address');
Route::post('/checkout/shipping', [CheckoutController::class, 'updateShipping'])->name('checkout.shipping');
Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
Route::post('/checkout/place', [CheckoutController::class, 'place'])
    ->middleware('throttle:10,1')
    ->name('checkout.place');
Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation');

Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::middleware('auth')->group(function () {
    Route::get('/account/orders', [AccountController::class, 'ordersIndex'])->name('account.orders');
    Route::get('/account/orders/{order}', [AccountController::class, 'ordersShow'])->name('account.orders.show');
    Route::get('/account/addresses', [AccountController::class, 'addressesIndex'])->name('account.addresses');
    Route::post('/account/addresses', [AccountController::class, 'addressesStore'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [AccountController::class, 'addressesUpdate'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'addressesDestroy'])->name('account.addresses.destroy');
    Route::get('/account/wishlist', [AccountController::class, 'wishlistIndex'])->name('account.wishlist');

    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});
