<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Collection;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => [
                'homepage_banner_url' => StoreSetting::get('homepage_banner_url'),
                'featured_collection_id' => StoreSetting::get('featured_collection_id'),
                'store_currency' => StoreSetting::get('store_currency', 'PKR'),
                'tax_mode' => StoreSetting::get('tax_mode', 'exclusive'),
            ],
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

        StoreSetting::set('homepage_banner_url', $data['homepage_banner_url']);
        StoreSetting::set('featured_collection_id', $data['featured_collection_id']);
        StoreSetting::set('store_currency', strtoupper($data['store_currency']));
        StoreSetting::set('tax_mode', $data['tax_mode']);

        return back()->with('success', 'Settings saved.');
    }
}
