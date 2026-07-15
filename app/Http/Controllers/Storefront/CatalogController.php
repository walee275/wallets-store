<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()
            ->active()
            ->with([
                'variants' => fn ($q) => $q->where('is_default', true)->where('is_active', true),
                'primaryImage',
                'categories',
            ]);

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q
                ->where('slug', $request->string('category'))
                ->where('is_active', true)
            );
        }

        if ($request->filled('min_price')) {
            $query->whereHas('variants', fn ($q) => $q
                ->where('is_default', true)
                ->where('is_active', true)
                ->where('price_cents', '>=', Money::fromMajor($request->input('min_price')))
            );
        }

        if ($request->filled('max_price')) {
            $query->whereHas('variants', fn ($q) => $q
                ->where('is_default', true)
                ->where('is_active', true)
                ->where('price_cents', '<=', Money::fromMajor($request->input('max_price')))
            );
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $defaultVariantPrice = ProductVariant::query()
            ->select('price_cents')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_default', true)
            ->where('is_active', true)
            ->limit(1);

        match ($request->input('sort', 'newest')) {
            'price_asc' => $query->orderBy($defaultVariantPrice, 'asc'),
            'price_desc' => $query->orderBy($defaultVariantPrice, 'desc'),
            default => $query->latest('published_at')->latest('id'),
        };

        $products = $query->paginate(12)->withQueryString();

        $products->getCollection()->transform(function (Product $product) {
            $product->setRelation('defaultVariant', $product->variants->first());

            return $product;
        });

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Storefront/Catalog/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category', 'min_price', 'max_price', 'sort', 'q']),
        ]);
    }

    public function show(string $slug): Response
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('is_default', 'desc'),
                'variants.optionValues.optionType',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position'),
                'reviews' => fn ($q) => $q
                    ->where('is_approved', true)
                    ->with('user:id,name')
                    ->latest(),
                'categories',
            ])
            ->firstOrFail();

        $defaultVariant = $product->variants->firstWhere('is_default', true)
            ?? $product->variants->first();

        $relatedProducts = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn ($q) => $q
                ->whereIn('categories.id', $product->categories->pluck('id'))
            )
            ->with([
                'variants' => fn ($q) => $q->where('is_default', true)->where('is_active', true),
                'primaryImage',
            ])
            ->limit(4)
            ->get();

        $relatedProducts->transform(function (Product $related) {
            $related->setRelation('defaultVariant', $related->variants->first());

            return $related;
        });

        return Inertia::render('Storefront/Product/Show', [
            'product' => $product,
            'defaultVariant' => $defaultVariant,
            'relatedProducts' => $relatedProducts,
            'jsonLd' => $this->buildProductJsonLd($product, $defaultVariant),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildProductJsonLd(Product $product, ?ProductVariant $defaultVariant): array
    {
        $images = $product->images->pluck('path')->filter()->values()->all();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'description' => $product->description,
            'url' => url('/products/'.$product->slug),
            'image' => $images,
        ];

        if ($product->brand) {
            $jsonLd['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand,
            ];
        }

        if ($defaultVariant) {
            $jsonLd['sku'] = $defaultVariant->sku;
            $jsonLd['offers'] = [
                '@type' => 'Offer',
                'price' => number_format($defaultVariant->price_cents / 100, 2, '.', ''),
                'priceCurrency' => $defaultVariant->currency,
                'availability' => $defaultVariant->stock_quantity > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => url('/products/'.$product->slug),
            ];
        }

        $approvedReviews = $product->reviews->where('is_approved', true);

        if ($approvedReviews->isNotEmpty()) {
            $jsonLd['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($approvedReviews->avg('rating'), 1),
                'reviewCount' => $approvedReviews->count(),
            ];
        }

        return $jsonLd;
    }
}
