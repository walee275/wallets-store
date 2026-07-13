<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function store(StoreReviewRequest $request): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $user = $request->user();

        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('payment_status', PaymentStatus::Paid)
            ->whereHas('items.variant', fn ($query) => $query->where('product_id', $product->id))
            ->latest('placed_at')
            ->first();

        Review::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $order?->id,
            'rating' => $request->integer('rating'),
            'body' => $request->input('body'),
            'is_approved' => false,
        ]);

        return back()->with('success', 'Thank you for your review. It will appear after approval.');
    }
}
