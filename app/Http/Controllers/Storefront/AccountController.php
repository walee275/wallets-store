<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreAddressRequest;
use App\Http\Requests\Storefront\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function ordersIndex(): Response
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with(['items', 'payments'])
            ->latest('placed_at')
            ->paginate(10);

        return Inertia::render('Storefront/Account/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function ordersShow(Order $order): Response
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['items', 'payments.paymentMethod', 'shippingRate', 'statusHistories']);

        return Inertia::render('Storefront/Account/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function addressesIndex(): Response
    {
        $addresses = auth()->user()->addresses()->orderByDesc('is_default')->get();

        return Inertia::render('Storefront/Account/Addresses/Index', [
            'addresses' => $addresses,
        ]);
    }

    public function addressesStore(StoreAddressRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['is_default'] ?? false) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        auth()->user()->addresses()->create($data);

        return back()->with('success', 'Address added.');
    }

    public function addressesUpdate(Address $address, UpdateAddressRequest $request): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $data = $request->validated();

        if ($data['is_default'] ?? false) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return back()->with('success', 'Address updated.');
    }

    public function addressesDestroy(Address $address): RedirectResponse
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    public function wishlistIndex(): Response
    {
        $wishlist = Wishlist::query()
            ->firstOrCreate(['user_id' => auth()->id()])
            ->load([
                'items' => fn ($query) => $query->with([
                    'variant.product.images' => fn ($q) => $q->where('is_primary', true)->orderBy('position'),
                    'variant.optionValues.optionType',
                ]),
            ]);

        return Inertia::render('Storefront/Account/Wishlist/Index', [
            'wishlist' => $wishlist,
        ]);
    }
}
