<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\StoreSetting;
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
                        'images' => fn ($q) => $q->where('is_primary', true)->orderBy('position'),
                    ])
                    ->orderByPivot('position');
            }])
            ->first();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $homepageBanner = StoreSetting::query()
            ->where('key', 'homepage')
            ->value('value_json');

        return Inertia::render('Storefront/Home', [
            'featuredCollection' => $featuredCollection,
            'featuredProducts' => $featuredCollection?->products ?? collect(),
            'categories' => $categories,
            'homepageBanner' => $homepageBanner,
        ]);
    }
}
