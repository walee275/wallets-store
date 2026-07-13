<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\DashboardMetrics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'revenue_cents' => $metrics->revenue(),
                'order_count' => $metrics->orderCount(),
                'average_order_value' => $metrics->averageOrderValue(),
                'top_products' => $metrics->topProducts(),
            ],
            'low_stock_variants_count' => ProductVariant::query()
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
        ]);
    }
}
