<?php

namespace App\Providers;

use App\Payments\PaymentGatewayManager;
use App\Services\StoreContent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->singleton(StoreContent::class);
    }

    public function boot(): void
    {
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        View::composer('emails.layouts.order', function ($view): void {
            $branding = app(StoreContent::class)->branding();

            $view->with('store', [
                'name' => $branding['name'],
                'tagline' => $branding['tagline'],
                'care_email' => $branding['care_email'],
                'location' => $branding['location'],
                'preferences_url' => config('store.preferences_url'),
            ]);
        });
    }
}
