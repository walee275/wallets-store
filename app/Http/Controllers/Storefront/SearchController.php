<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $products = Product::query()
            ->active()
            ->where('title', 'like', $term.'%')
            ->with([
                'variants' => fn ($q) => $q->where('is_default', true)->where('is_active', true),
                'images' => fn ($q) => $q->where('is_primary', true)->orderBy('position'),
            ])
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title', 'slug']);

        return response()->json(
            $products->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'price_cents' => $product->variants->first()?->price_cents ?? 0,
                'image' => $product->images->first()?->path,
            ])->values()
        );
    }
}
