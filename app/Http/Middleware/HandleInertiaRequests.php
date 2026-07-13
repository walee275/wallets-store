<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\Category;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $cartCount = 0;

        try {
            $cartCount = $this->resolveCartCount($request);
        } catch (\Throwable) {
            $cartCount = 0;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'type']),
                'isAdmin' => $request->user()?->isAdmin() ?? false,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'cartCount' => $cartCount,
            'navCategories' => fn () => Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'store' => fn () => [
                'currency' => StoreSetting::get('currency', 'PKR'),
                'name' => config('app.name', 'Commerce'),
            ],
        ];
    }

    protected function resolveCartCount(Request $request): int
    {
        $cart = Auth::check()
            ? Cart::query()->with('items')->where('user_id', Auth::id())->first()
            : Cart::query()->with('items')->where('session_id', $request->session()->getId())->first();

        return (int) ($cart?->items->sum('quantity') ?? 0);
    }
}
