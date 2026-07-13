<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\AddToCart;
use App\Actions\Cart\ResolveCart;
use App\Actions\Cart\UpdateCartItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CartController extends Controller
{
    public function index(ResolveCart $resolveCart): Response
    {
        $cart = $resolveCart->handle();

        return Inertia::render('Storefront/Cart/Index', [
            'cart' => $cart,
        ]);
    }

    public function store(AddToCartRequest $request, ResolveCart $resolveCart, AddToCart $addToCart): RedirectResponse
    {
        try {
            $addToCart->handle(
                $resolveCart->handle(),
                $request->integer('variant_id'),
                $request->integer('quantity'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cart' => $exception->getMessage()]);
        }

        return back()->with('success', 'Item added to cart.');
    }

    public function update(
        CartItem $item,
        UpdateCartItemRequest $request,
        ResolveCart $resolveCart,
        UpdateCartItem $updateCartItem,
    ): RedirectResponse {
        $cart = $resolveCart->handle();

        abort_unless($item->cart_id === $cart->id, 403);

        $updateCartItem->handle($item, $request->integer('quantity'));

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $item, ResolveCart $resolveCart): RedirectResponse
    {
        $cart = $resolveCart->handle();

        abort_unless($item->cart_id === $cart->id, 403);

        $item->delete();

        return back()->with('success', 'Item removed from cart.');
    }
}
