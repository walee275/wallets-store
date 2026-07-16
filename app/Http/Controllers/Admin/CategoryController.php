<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->with('parent')
                ->orderBy('position')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create', [
            'parents' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image']);

        $category = Category::query()->create($data);

        if ($request->hasFile('image')) {
            $category->update([
                'image_path' => $this->storeCategoryImage($request->file('image')),
            ]);
        }

        return to_route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
            'parents' => Category::query()
                ->whereKeyNot($category->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        $category->update($data);

        if ($request->hasFile('image')) {
            $this->deleteCategoryImage($category);
            $category->update([
                'image_path' => $this->storeCategoryImage($request->file('image')),
            ]);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteCategoryImage($category);
            $category->update(['image_path' => null]);
        }

        return to_route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->deleteCategoryImage($category);
        $category->delete();

        return to_route('admin.categories.index')->with('success', 'Category deleted.');
    }

    protected function storeCategoryImage(UploadedFile $file): string
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $path = 'categories/'.Str::uuid().'.'.$extension;

        $image = Image::decode($file)->scaleDown(1200, 1200);
        $disk->put($path, (string) $image->encodeUsingFileExtension($extension));

        return $path;
    }

    protected function deleteCategoryImage(Category $category): void
    {
        if (! $category->image_path) {
            return;
        }

        Storage::disk(config('media.disk', 'public'))->delete($category->image_path);
    }
}
