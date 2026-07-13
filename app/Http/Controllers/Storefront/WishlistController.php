<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\WishlistToggleRequest;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WishlistController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function toggle(WishlistToggleRequest $request): RedirectResponse
    {
        $wishlist = Wishlist::query()->firstOrCreate(['user_id' => auth()->id()]);
        $variantId = $request->integer('variant_id');

        $item = $wishlist->items()->where('variant_id', $variantId)->first();

        if ($item) {
            $item->delete();

            return back()->with('wishlist', [
                'variant_id' => $variantId,
                'added' => false,
            ]);
        }

        $wishlist->items()->create(['variant_id' => $variantId]);

        return back()->with('wishlist', [
            'variant_id' => $variantId,
            'added' => true,
        ]);
    }
}
