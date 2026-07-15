<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Money;
use App\Support\ProductEmailImage;
use App\Support\SkuGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $products = Product::query()
            ->with(['variants' => fn ($query) => $query->where('is_default', true)])
            ->withCount('variants')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('sku', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(ProductStatus::cases())->map(fn (ProductStatus $status) => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $product = Product::query()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'brand' => $data['brand'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'published_at' => $data['published_at'] ?? null,
            ]);

            $this->syncVariants($product, $data['default_variant'], $data['variants'] ?? [], isCreate: true);

            if (! empty($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }

            if ($request->hasFile('images')) {
                $this->storeProductImages($product, $request->file('images'));
            }
        });

        return to_route('admin.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): Response
    {
        $product->load(['variants', 'images', 'categories']);

        return Inertia::render('Admin/Products/Show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load(['variants', 'images', 'categories']);

        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(ProductStatus::cases())->map(fn (ProductStatus $status) => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ]),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($product, $data, $request) {
            $product->update([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'brand' => $data['brand'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'published_at' => $data['published_at'] ?? null,
            ]);

            $this->syncVariants($product, $data['default_variant'], $data['variants'] ?? []);

            $product->categories()->sync($data['category_ids'] ?? []);

            if (! empty($data['removed_image_ids'])) {
                $this->removeProductImages($product, $data['removed_image_ids']);
            }

            $uploadedImageIds = [];

            if ($request->hasFile('images')) {
                $uploadedImageIds = $this->storeProductImages($product, $request->file('images'));
            }

            $primaryImageId = $data['primary_image_id'] ?? null;
            $uploadIndex = $data['primary_image_upload_index'] ?? null;

            if ($uploadIndex !== null && isset($uploadedImageIds[$uploadIndex])) {
                $primaryImageId = $uploadedImageIds[$uploadIndex];
            }

            if ($primaryImageId !== null) {
                $this->setPrimaryProductImage($product, (int) $primaryImageId);
            }
        });

        return to_route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $writer = Writer::createFromStream(fopen('php://output', 'w'));
            $writer->insertOne(['title', 'slug', 'sku', 'price', 'stock', 'description', 'status']);

            Product::query()
                ->with('variants')
                ->orderBy('title')
                ->each(function (Product $product) use ($writer) {
                    foreach ($product->variants as $variant) {
                        $writer->insertOne([
                            $product->title,
                            $product->slug,
                            $variant->sku,
                            Money::toMajor($variant->price_cents),
                            $variant->stock_quantity,
                            $product->description,
                            $product->status->value,
                        ]);
                    }
                });
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(ImportProductsRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $reader = Reader::createFromPath($file->getRealPath());
        $reader->setHeaderOffset(0);

        $imported = 0;

        DB::transaction(function () use ($reader, &$imported) {
            foreach ($reader->getRecords() as $record) {
                $title = trim((string) ($record['title'] ?? ''));
                $slug = trim((string) ($record['slug'] ?? '')) ?: Str::slug($title);
                $sku = trim((string) ($record['sku'] ?? '')) ?: SkuGenerator::generate();
                $status = ProductStatus::tryFrom(strtolower(trim((string) ($record['status'] ?? 'draft')))) ?? ProductStatus::Draft;

                if ($title === '') {
                    continue;
                }

                $product = Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $title,
                        'description' => $record['description'] ?? null,
                        'status' => $status,
                    ],
                );

                $variantData = [
                    'price_cents' => Money::fromMajor($record['price'] ?? 0),
                    'stock_quantity' => (int) ($record['stock'] ?? 0),
                    'is_default' => true,
                    'is_active' => true,
                    'currency' => 'PKR',
                ];

                $existingVariant = ProductVariant::query()->where('sku', $sku)->first();

                if ($existingVariant && $existingVariant->product_id === $product->id) {
                    $existingVariant->update($variantData);
                } elseif (! $existingVariant) {
                    $product->variants()->create(array_merge($variantData, ['sku' => $sku]));
                } else {
                    $product->variants()->updateOrCreate(
                        ['sku' => SkuGenerator::generate()],
                        $variantData,
                    );
                }

                if (! $product->variants()->where('is_default', true)->exists()) {
                    $product->variants()->first()?->update(['is_default' => true]);
                }

                $imported++;
            }
        });

        return to_route('admin.products.index')->with('success', "{$imported} product row(s) imported.");
    }

    /**
     * @param  array<string, mixed>  $defaultVariant
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function syncVariants(Product $product, array $defaultVariant, array $variants, bool $isCreate = false): void
    {
        if (! $isCreate) {
            $product->variants()->update(['is_default' => false]);
        }

        $defaultPayload = $this->variantPayload($defaultVariant, isDefault: true);
        $defaultPayload['sku'] = $defaultVariant['sku'] ?? SkuGenerator::generate();

        if ($isCreate || empty($defaultVariant['id'])) {
            $product->variants()->create($defaultPayload);
        } else {
            $product->variants()->whereKey($defaultVariant['id'])->update($defaultPayload);
        }

        $keptIds = array_filter([$defaultVariant['id'] ?? null]);

        foreach ($variants as $variant) {
            $payload = $this->variantPayload($variant, isDefault: false);

            if (! empty($variant['id'])) {
                $product->variants()->whereKey($variant['id'])->update($payload);
                $keptIds[] = $variant['id'];
            } else {
                $payload['sku'] = $variant['sku'] ?? SkuGenerator::generate();
                $created = $product->variants()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        if (! $isCreate) {
            $product->variants()
                ->where('is_default', false)
                ->whereNotIn('id', $keptIds)
                ->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $variant
     * @return array<string, mixed>
     */
    protected function variantPayload(array $variant, bool $isDefault): array
    {
        return [
            'sku' => $variant['sku'] ?? null,
            'price_cents' => Money::fromMajor($variant['price']),
            'compare_at_cents' => isset($variant['compare_at_price']) ? Money::fromMajor($variant['compare_at_price']) : null,
            'cost_cents' => isset($variant['cost_price']) ? Money::fromMajor($variant['cost_price']) : null,
            'stock_quantity' => (int) $variant['stock'],
            'low_stock_threshold' => (int) ($variant['low_stock_threshold'] ?? 5),
            'weight_grams' => $variant['weight_grams'] ?? null,
            'currency' => $variant['currency'] ?? 'PKR',
            'is_default' => $isDefault,
            'is_active' => true,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, int>
     */
    protected function storeProductImages(Product $product, array $files): array
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        $position = (int) $product->images()->max('position') + 1;
        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $imageIds = [];

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $path = 'products/'.Str::uuid().'.'.$extension;

            $image = Image::decode($file)->scaleDown(1200, 1200);
            $disk->put($path, (string) $image->encodeUsingFileExtension($extension));

            $productImage = ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->title,
                'position' => $position++,
                'is_primary' => ! $hasPrimary,
            ]);

            $hasPrimary = true;
            $imageIds[] = $productImage->id;

            ProductEmailImage::generateThumb($productImage);
        }

        return $imageIds;
    }

    /**
     * @param  array<int, int>  $imageIds
     */
    protected function removeProductImages(Product $product, array $imageIds): void
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        $images = $product->images()->whereKey($imageIds)->get();
        $removedPrimary = $images->contains('is_primary', true);

        foreach ($images as $image) {
            $disk->delete(array_filter([
                $image->path,
                $image->email_thumb_path,
            ]));

            $image->delete();
        }

        if ($removedPrimary) {
            $product->images()
                ->orderBy('position')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }
    }

    protected function setPrimaryProductImage(Product $product, int $imageId): void
    {
        $image = $product->images()->whereKey($imageId)->first();

        if (! $image) {
            return;
        }

        $product->images()->where('is_primary', true)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
    }
}
