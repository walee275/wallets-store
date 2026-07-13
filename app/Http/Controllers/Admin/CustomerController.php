<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $customers = User::query()
            ->where('type', 'customer')
            ->withCount('orders')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(User $customer): Response
    {
        abort_unless($customer->type === 'customer', 404);

        $customer->load([
            'orders' => fn ($query) => $query->latest()->limit(50),
            'addresses',
        ]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
        ]);
    }

    public function deactivate(User $customer): RedirectResponse
    {
        abort_unless($customer->type === 'customer', 404);

        $customer->update(['is_active' => false]);

        return back()->with('success', 'Customer deactivated.');
    }
}
