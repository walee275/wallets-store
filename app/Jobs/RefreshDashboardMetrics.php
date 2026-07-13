<?php

namespace App\Jobs;

use App\Services\DashboardMetrics;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshDashboardMetrics implements ShouldQueue
{
    use Queueable;

    public function handle(DashboardMetrics $dashboardMetrics): void
    {
        if (method_exists($dashboardMetrics, 'clear')) {
            $dashboardMetrics->clear();
        } elseif (method_exists($dashboardMetrics, 'refresh')) {
            $dashboardMetrics->refresh();
        } else {
            Cache::forget('dashboard.metrics.revenue');
            Cache::forget('dashboard.metrics.order_count');
            Cache::forget('dashboard.metrics.aov');

            foreach ([5, 10, 20] as $limit) {
                Cache::forget("dashboard.metrics.top_products.{$limit}");
            }
        }
    }
}
