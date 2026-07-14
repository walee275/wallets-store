<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\ResolveCart;
use App\Actions\Checkout\ApplyDiscount;
use App\Actions\Checkout\CreateOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutAddressRequest;
use App\Http\Requests\Storefront\CheckoutCouponRequest;
use App\Http\Requests\Storefront\CheckoutPlaceRequest;
use App\Http\Requests\Storefront\CheckoutShippingRequest;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Payments\PaymentGatewayManager;
use App\Services\CheckoutCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function show(
        Request $request,
        ResolveCart $resolveCart,
        PaymentGatewayManager $paymentGatewayManager,
        CheckoutCalculator $checkoutCalculator,
    ): Response|RedirectResponse {
        $cart = $resolveCart->handle();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $shippingRates = $this->shippingRatesForCountry('PK');

        $paymentMethods = $paymentGatewayManager->enabledGateways();

        $addresses = Auth::check()
            ? $request->user()->addresses()->orderByDesc('is_default')->get()
            : collect();

        $shippingRateId = $request->session()->get('checkout.shipping_rate_id');

        // Persist the cheapest available rate when nothing is stored yet, so the
        // UI default and place-order session check stay in sync.
        if (! $shippingRateId && $shippingRates->isNotEmpty()) {
            $shippingRateId = $shippingRates->first()->id;
            $request->session()->put('checkout.shipping_rate_id', $shippingRateId);
        }

        $checkoutState = [
            'address' => $request->session()->get('checkout.address'),
            'shipping_rate_id' => $shippingRateId,
            'discount_code' => $request->session()->get('checkout.discount_code'),
        ];

        $totals = $this->calculateTotals($cart, $checkoutState, $checkoutCalculator, $request->user());

        return Inertia::render('Storefront/Checkout/Index', [
            'cart' => $cart,
            'shippingRates' => $shippingRates,
            'paymentMethods' => $paymentMethods,
            'addresses' => $addresses,
            'checkout' => $checkoutState,
            'totals' => $totals,
        ]);
    }

    public function updateAddress(CheckoutAddressRequest $request): RedirectResponse
    {
        $request->session()->put('checkout.address', $request->validated());

        return back()->with('success', 'Shipping address saved.');
    }

    public function updateShipping(CheckoutShippingRequest $request): RedirectResponse
    {
        $request->session()->put('checkout.shipping_rate_id', $request->integer('shipping_rate_id'));

        return back()->with('success', 'Shipping method updated.');
    }

    public function applyCoupon(
        CheckoutCouponRequest $request,
        ResolveCart $resolveCart,
        ApplyDiscount $applyDiscount,
    ): RedirectResponse {
        $cart = $resolveCart->handle();
        $subtotalCents = $this->cartSubtotalCents($cart);

        try {
            $discount = $applyDiscount->handle(
                $request->string('code')->toString(),
                $subtotalCents,
                $request->user(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->put('checkout.discount_code', $discount->code);

        return back()->with('success', 'Discount code applied.');
    }

    public function place(
        CheckoutPlaceRequest $request,
        ResolveCart $resolveCart,
        CreateOrder $createOrder,
    ): RedirectResponse {
        $cart = $resolveCart->handle();
        $address = $request->session()->get('checkout.address');

        // Prefer an explicit selection from the place-order form, then session.
        $shippingRateId = $request->integer('shipping_rate_id')
            ?: $request->session()->get('checkout.shipping_rate_id');

        if (! is_array($address) || empty($address)) {
            return back()->withErrors(['address' => 'Please provide a shipping address.']);
        }

        if (! $shippingRateId) {
            return back()->withErrors(['shipping_rate_id' => 'Please select a shipping method.']);
        }

        $request->session()->put('checkout.shipping_rate_id', (int) $shippingRateId);

        $email = $request->user()?->email ?? $request->string('email')->toString();
        $billingAddress = $request->boolean('billing_same_as_shipping', true)
            ? $address
            : $request->validated('billing_address');

        try {
            $result = $createOrder->handle(
                cart: $cart,
                shippingAddress: $address,
                billingAddress: $billingAddress,
                email: $email,
                user: $request->user(),
                shippingRateId: (int) $shippingRateId,
                paymentDriver: $request->string('payment_driver')->toString(),
                discountCode: $request->session()->get('checkout.discount_code'),
                notes: $request->input('notes'),
            );
        } catch (InvalidArgumentException|\RuntimeException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        $request->session()->forget([
            'checkout.address',
            'checkout.shipping_rate_id',
            'checkout.discount_code',
        ]);

        $request->session()->put('checkout.last_order_id', $result['order']->id);

        if ($result['initiation']->redirectUrl) {
            return redirect()->away($result['initiation']->redirectUrl);
        }

        return redirect()->route('checkout.confirmation', $result['order']);
    }

    public function confirmation(Request $request, Order $order): Response
    {
        if ($request->user() && $order->user_id !== $request->user()->id) {
            abort(403);
        }

        $order->load(['items', 'payments.paymentMethod', 'shippingRate']);

        return Inertia::render('Storefront/Checkout/Confirmation', [
            'order' => $order,
        ]);
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    protected function shippingRatesForCountry(string $country)
    {
        return ShippingRate::query()
            ->where('is_active', true)
            ->whereHas('zone', fn ($query) => $query
                ->where('is_active', true)
                ->whereJsonContains('countries_json', $country)
            )
            ->with('zone')
            ->orderBy('price_cents')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $checkoutState
     * @return array<string, int>
     */
    protected function calculateTotals($cart, array $checkoutState, CheckoutCalculator $calculator, $user): array
    {
        $calculatorItems = $cart->items->map(fn ($item) => (object) [
            'quantity' => $item->quantity,
            'unit_price_cents' => $item->variant->price_cents,
        ]);

        $discount = null;

        if (! empty($checkoutState['discount_code'])) {
            try {
                $discount = app(ApplyDiscount::class)->handle(
                    (string) $checkoutState['discount_code'],
                    $this->cartSubtotalCents($cart),
                    $user,
                );
            } catch (InvalidArgumentException) {
                $discount = null;
            }
        }

        $shippingCents = 0;

        if (! empty($checkoutState['shipping_rate_id'])) {
            $shippingCents = (int) (ShippingRate::query()
                ->whereKey($checkoutState['shipping_rate_id'])
                ->where('is_active', true)
                ->value('price_cents') ?? 0);
        }

        return $calculator->calculate($calculatorItems, $discount, $shippingCents);
    }

    protected function cartSubtotalCents($cart): int
    {
        return (int) $cart->items->sum(
            fn ($item) => $item->quantity * $item->variant->price_cents
        );
    }
}
