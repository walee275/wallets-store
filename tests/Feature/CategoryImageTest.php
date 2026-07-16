<?php

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function makeCategoryJpegUpload(string $name = 'category.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 800, 600);
}

test('admin can create a category with an image', function () {
    Storage::fake(config('media.disk', 'public'));

    $admin = createAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Wallets',
            'slug' => 'wallets',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
            'image' => makeCategoryJpegUpload(),
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasNoErrors();

    $category = Category::query()->where('slug', 'wallets')->first();

    expect($category)->not->toBeNull()
        ->and($category->image_path)->not->toBeNull()
        ->and($category->image_path)->toStartWith('categories/');

    Storage::disk(config('media.disk', 'public'))->assertExists($category->image_path);
});

test('admin can replace and remove a category image', function () {
    Storage::fake(config('media.disk', 'public'));

    $admin = createAdminUser();

    $category = Category::query()->create([
        'name' => 'Bags',
        'slug' => 'bags',
        'position' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.categories.update', $category), [
            '_method' => 'put',
            'name' => 'Bags',
            'slug' => 'bags',
            'parent_id' => null,
            'position' => 0,
            'is_active' => true,
            'image' => makeCategoryJpegUpload('bags-one.jpg'),
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasNoErrors();

    $category->refresh();
    $originalPath = $category->image_path;

    expect($originalPath)->not->toBeNull();
    Storage::disk(config('media.disk', 'public'))->assertExists($originalPath);

    $this->actingAs($admin)
        ->post(route('admin.categories.update', $category), [
            '_method' => 'put',
            'name' => 'Bags',
            'slug' => 'bags',
            'parent_id' => null,
            'position' => 0,
            'is_active' => true,
            'image' => makeCategoryJpegUpload('bags-two.jpg'),
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasNoErrors();

    $category->refresh();

    expect($category->image_path)->not->toBe($originalPath);
    Storage::disk(config('media.disk', 'public'))->assertMissing($originalPath);
    Storage::disk(config('media.disk', 'public'))->assertExists($category->image_path);

    $replacementPath = $category->image_path;

    $this->actingAs($admin)
        ->post(route('admin.categories.update', $category), [
            '_method' => 'put',
            'name' => 'Bags',
            'slug' => 'bags',
            'parent_id' => null,
            'position' => 0,
            'is_active' => true,
            'remove_image' => true,
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHasNoErrors();

    $category->refresh();

    expect($category->image_path)->toBeNull();
    Storage::disk(config('media.disk', 'public'))->assertMissing($replacementPath);
});

test('homepage includes category image_path for storefront tiles', function () {
    $category = Category::query()->create([
        'name' => 'Belts',
        'slug' => 'belts',
        'image_path' => 'categories/belts.jpg',
        'position' => 0,
        'is_active' => true,
        'parent_id' => null,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.image_path', 'categories/belts.jpg')
        );
});
