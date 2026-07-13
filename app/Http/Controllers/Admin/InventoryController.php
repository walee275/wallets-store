<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Inventory\AdjustInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Inventory/Index', [
            'variants' => ProductVariant::query()
                ->with('product:id,title,slug')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->orderBy('stock_quantity')
                ->paginate(25),
        ]);
    }

    public function adjust(AdjustInventoryRequest $request, ProductVariant $variant, AdjustInventory $adjustInventory): RedirectResponse
    {
        $data = $request->validated();

        $adjustInventory->handle(
            variant: $variant,
            delta: (int) $data['delta'],
            type: $data['type'],
            userId: $request->user()->id,
            note: $data['note'] ?? null,
        );

        return back()->with('success', 'Inventory adjusted.');
    }
}
