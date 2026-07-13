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

            if ($request->hasFile('image')) {
                $this->storeProductImage($product, $request->file('image'));
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

            if ($request->hasFile('image')) {
                $this->storeProductImage($product, $request->file('image'));
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

    protected function storeProductImage(Product $product, UploadedFile $file): void
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = 'products/'.$filename;

        $image = Image::read($file)->scaleDown(1200, 1200);
        Storage::disk('public')->put($path, (string) $image->encode());

        $product->images()->update(['is_primary' => false]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $path,
            'alt' => $product->title,
            'position' => 0,
            'is_primary' => true,
        ]);
    }
}
