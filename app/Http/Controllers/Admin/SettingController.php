<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Collection;
use App\Models\StoreSetting;
use App\Services\StoreContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Laravel\Facades\Image;

class SettingController extends Controller
{
    public function __construct(protected StoreContent $storeContent) {}

    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'content' => $this->storeContent->all(),
            'collections' => Collection::query()->orderBy('name')->get(['id', 'name']),
            'taxModes' => [
                ['value' => 'inclusive', 'label' => 'Inclusive'],
                ['value' => 'exclusive', 'label' => 'Exclusive'],
                ['value' => 'none', 'label' => 'None'],
            ],
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $existingBranding = is_array(StoreSetting::get('branding')) ? StoreSetting::get('branding') : [];
        $existingSeo = is_array(StoreSetting::get('seo')) ? StoreSetting::get('seo') : [];

        $branding = [
            'name' => $data['branding']['name'],
            'tagline' => $data['branding']['tagline'] ?? null,
            'location' => $data['branding']['location'] ?? null,
            'care_email' => $data['branding']['care_email'] ?? null,
            'phone' => $data['branding']['phone'] ?? null,
            'header_logo_path' => $this->resolveLogoPath(
                $request->file('branding.header_logo'),
                $existingBranding['header_logo_path'] ?? null,
                (bool) ($data['branding']['remove_header_logo'] ?? false),
            ),
            'footer_logo_path' => $this->resolveLogoPath(
                $request->file('branding.footer_logo'),
                $existingBranding['footer_logo_path'] ?? null,
                (bool) ($data['branding']['remove_footer_logo'] ?? false),
            ),
        ];

        $seo = [
            'default_description' => $data['seo']['default_description'] ?? null,
            'title_suffix' => $data['seo']['title_suffix'] ?? null,
            'og_image_path' => $this->resolveLogoPath(
                $request->file('seo.og_image'),
                $existingSeo['og_image_path'] ?? null,
                (bool) ($data['seo']['remove_og_image'] ?? false),
            ),
        ];

        StoreSetting::set('branding', $branding);
        StoreSetting::set('social', $data['social'] ?? []);
        StoreSetting::set('footer', $data['footer'] ?? []);
        StoreSetting::set('homepage', $data['homepage'] ?? []);
        StoreSetting::set('seo', $seo);
        StoreSetting::set('auth', $data['auth'] ?? []);
        StoreSetting::set('currency', strtoupper($data['currency']));
        StoreSetting::set('tax_mode', $data['tax_mode']);
        StoreSetting::set('featured_collection_id', $data['featured_collection_id'] ?? null);

        return back()->with('success', 'Settings saved.');
    }

    protected function resolveLogoPath(?UploadedFile $file, ?string $existingPath, bool $remove): ?string
    {
        if ($remove && $existingPath) {
            $this->deleteStoredImage($existingPath);
            $existingPath = null;
        }

        if (! $file) {
            return $existingPath;
        }

        if ($existingPath) {
            $this->deleteStoredImage($existingPath);
        }

        return $this->storeBrandingImage($file);
    }

    protected function storeBrandingImage(UploadedFile $file): string
    {
        $disk = Storage::disk(config('media.disk', 'public'));
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $path = 'branding/'.Str::uuid().'.'.$extension;

        $image = Image::decode($file)->scaleDown(1200, 1200);
        $disk->put($path, (string) $image->encodeUsingFileExtension($extension));

        return $path;
    }

    protected function deleteStoredImage(string $path): void
    {
        Storage::disk(config('media.disk', 'public'))->delete($path);
    }
}
