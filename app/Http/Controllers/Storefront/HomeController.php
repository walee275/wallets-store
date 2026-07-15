<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $featuredCollection = Collection::query()
            ->where('is_featured', true)
            ->with(['products' => function ($query) {
                $query->active()
                    ->with([
                        'variants' => fn ($q) => $q->where('is_default', true)->where('is_active', true),
                        'primaryImage',
                    ])
                    ->orderByPivot('position')
                    ->limit(4);
            }])
            ->first();

        $featuredProducts = ($featuredCollection?->products ?? collect())
            ->take(4)
            ->values()
            ->map(function ($product) {
                $product->setRelation('defaultVariant', $product->variants->first());

                return $product;
            });

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
            ->orderBy('position')
            ->limit(3)
            ->get();

        return Inertia::render('Storefront/Home', [
            'featuredCollection' => $featuredCollection
                ? $featuredCollection->only(['id', 'name'])
                : null,
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }
}
