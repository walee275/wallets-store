<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingRateRequest;
use App\Http\Requests\Admin\StoreShippingZoneRequest;
use App\Http\Requests\Admin\UpdateShippingRateRequest;
use App\Http\Requests\Admin\UpdateShippingZoneRequest;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShippingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Shipping/Index', [
            'zones' => ShippingZone::query()
                ->with('rates')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeZone(StoreShippingZoneRequest $request): RedirectResponse
    {
        ShippingZone::query()->create($request->validated());

        return back()->with('success', 'Shipping zone created.');
    }

    public function storeRate(StoreShippingRateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        ShippingRate::query()->create([
            'zone_id' => $data['zone_id'],
            'name' => $data['name'],
            'price_cents' => Money::fromMajor($data['price']),
            'min_weight' => $data['min_weight'] ?? null,
            'max_weight' => $data['max_weight'] ?? null,
            'min_order_cents' => isset($data['min_order']) ? Money::fromMajor($data['min_order']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'Shipping rate created.');
    }

    public function updateZone(UpdateShippingZoneRequest $request, ShippingZone $zone): RedirectResponse
    {
        $zone->update($request->validated());

        return back()->with('success', 'Shipping zone updated.');
    }

    public function updateRate(UpdateShippingRateRequest $request, ShippingRate $rate): RedirectResponse
    {
        $data = $request->validated();

        $rate->update([
            'name' => $data['name'],
            'price_cents' => Money::fromMajor($data['price']),
            'min_weight' => $data['min_weight'] ?? null,
            'max_weight' => $data['max_weight'] ?? null,
            'min_order_cents' => isset($data['min_order']) ? Money::fromMajor($data['min_order']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'Shipping rate updated.');
    }

    public function destroyZone(ShippingZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Shipping zone deleted.');
    }

    public function destroyRate(ShippingRate $rate): RedirectResponse
    {
        $rate->delete();

        return back()->with('success', 'Shipping rate deleted.');
    }
}
