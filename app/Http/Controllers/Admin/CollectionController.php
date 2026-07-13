<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCollectionRequest;
use App\Http\Requests\Admin\SyncCollectionProductsRequest;
use App\Http\Requests\Admin\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Collections/Index', [
            'collections' => Collection::query()
                ->withCount('products')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Collections/Create');
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        Collection::query()->create($request->validated());

        return to_route('admin.collections.index')->with('success', 'Collection created.');
    }

    public function edit(Collection $collection): Response
    {
        $collection->load(['products' => fn ($query) => $query->orderByPivot('position')]);

        return Inertia::render('Admin/Collections/Edit', [
            'collection' => $collection,
            'products' => Product::query()->orderBy('title')->get(['id', 'title', 'slug']),
        ]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $collection->update($request->validated());

        return to_route('admin.collections.edit', $collection)->with('success', 'Collection updated.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return to_route('admin.collections.index')->with('success', 'Collection deleted.');
    }

    public function syncProducts(SyncCollectionProductsRequest $request, Collection $collection): RedirectResponse
    {
        $sync = collect($request->validated('product_ids'))
            ->values()
            ->mapWithKeys(fn (int $productId, int $position) => [$productId => ['position' => $position]]);

        $collection->products()->sync($sync->all());

        return back()->with('success', 'Collection products synced.');
    }
}
