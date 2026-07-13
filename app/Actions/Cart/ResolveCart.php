<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\Auth;

class ResolveCart
{
    public function handle(): Cart
    {
        if (Auth::check()) {
            $cart = Cart::query()->firstOrCreate(
                ['user_id' => Auth::id()],
                ['currency' => 'PKR'],
            );
        } else {
            $cart = Cart::query()->firstOrCreate(
                ['session_id' => session()->getId()],
                ['currency' => 'PKR'],
            );
        }

        return $cart->load(['items.variant.product.images']);
    }
}
