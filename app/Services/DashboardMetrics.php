<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    protected int $cacheTtlSeconds = 300;

    public function revenue(): int
    {
        return (int) Cache::remember('dashboard.metrics.revenue', $this->cacheTtlSeconds, function () {
            return Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->sum('total_cents');
        });
    }

    public function orderCount(): int
    {
        return (int) Cache::remember('dashboard.metrics.order_count', $this->cacheTtlSeconds, function () {
            return Order::query()->count();
        });
    }

    public function averageOrderValue(): float
    {
        return (float) Cache::remember('dashboard.metrics.aov', $this->cacheTtlSeconds, function () {
            $paidOrders = Order::query()
                ->where('payment_status', PaymentStatus::Paid);

            $count = (clone $paidOrders)->count();

            if ($count === 0) {
                return 0.0;
            }

            return round(((int) (clone $paidOrders)->sum('total_cents')) / $count / 100, 2);
        });
    }

    public function topProducts(int $limit = 10): Collection
    {
        return Cache::remember("dashboard.metrics.top_products.{$limit}", $this->cacheTtlSeconds, function () use ($limit) {
            return OrderItem::query()
                ->select([
                    'order_items.product_title',
                    'order_items.variant_id',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.total_cents) as total_revenue_cents'),
                ])
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.payment_status', PaymentStatus::Paid)
                ->groupBy('order_items.product_title', 'order_items.variant_id')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();
        });
    }
}
