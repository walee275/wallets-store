<?php

use App\Actions\Checkout\ExpireUnpaidOrders;
use App\Jobs\GenerateSitemapJob;
use App\Jobs\RefreshDashboardMetrics;
use App\Jobs\SendAbandonedCartReminder;
use App\Models\Cart;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(ExpireUnpaidOrders::class)->handle(60))
    ->everyFifteenMinutes()
    ->name('expire-unpaid-orders');

Schedule::job(new GenerateSitemapJob)->daily()->name('generate-sitemap');
Schedule::job(new RefreshDashboardMetrics)->hourly()->name('refresh-dashboard-metrics');

Schedule::call(function () {
    Cart::query()
        ->where('updated_at', '<', now()->subHours(24))
        ->whereHas('items')
        ->whereNotNull('user_id')
        ->with('user')
        ->limit(50)
        ->get()
        ->each(fn (Cart $cart) => SendAbandonedCartReminder::dispatch($cart));
})->hourly()->name('abandoned-cart-reminders');
