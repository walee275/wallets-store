<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDiscountRequest;
use App\Http\Requests\Admin\UpdateDiscountRequest;
use App\Models\Discount;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Discounts/Index', [
            'discounts' => Discount::query()->latest()->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Discounts/Create', [
            'types' => collect(DiscountType::cases())->map(fn (DiscountType $type) => [
                'value' => $type->value,
                'label' => str_replace('_', ' ', ucfirst($type->value)),
            ]),
        ]);
    }

    public function store(StoreDiscountRequest $request): RedirectResponse
    {
        Discount::query()->create($request->validated());

        return to_route('admin.discounts.index')->with('success', 'Discount created.');
    }

    public function edit(Discount $discount): Response
    {
        return Inertia::render('Admin/Discounts/Edit', [
            'discount' => $discount,
            'types' => collect(DiscountType::cases())->map(fn (DiscountType $type) => [
                'value' => $type->value,
                'label' => str_replace('_', ' ', ucfirst($type->value)),
            ]),
        ]);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): RedirectResponse
    {
        $discount->update($request->validated());

        return to_route('admin.discounts.index')->with('success', 'Discount updated.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return to_route('admin.discounts.index')->with('success', 'Discount deleted.');
    }
}
