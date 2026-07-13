<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

class ShippingRateResolver
{
    public function resolve(string $countryCode, ?int $weightGrams = null, ?int $subtotalCents = null): Collection
    {
        $countryCode = strtoupper($countryCode);

        $zones = ShippingZone::query()
            ->where('is_active', true)
            ->whereJsonContains('countries_json', $countryCode)
            ->get();

        if ($zones->isEmpty()) {
            return collect();
        }

        return ShippingRate::query()
            ->whereIn('zone_id', $zones->pluck('id'))
            ->where('is_active', true)
            ->when($weightGrams !== null, function ($query) use ($weightGrams) {
                $query->where(function ($inner) use ($weightGrams) {
                    $inner->whereNull('min_weight')
                        ->orWhere('min_weight', '<=', $weightGrams);
                })->where(function ($inner) use ($weightGrams) {
                    $inner->whereNull('max_weight')
                        ->orWhere('max_weight', '>=', $weightGrams);
                });
            })
            ->when($subtotalCents !== null, function ($query) use ($subtotalCents) {
                $query->where(function ($inner) use ($subtotalCents) {
                    $inner->whereNull('min_order_cents')
                        ->orWhere('min_order_cents', '<=', $subtotalCents);
                });
            })
            ->orderBy('price_cents')
            ->get();
    }
}
