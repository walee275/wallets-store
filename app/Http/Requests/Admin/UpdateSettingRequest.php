<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branding.name' => ['required', 'string', 'max:120'],
            'branding.tagline' => ['nullable', 'string', 'max:255'],
            'branding.location' => ['nullable', 'string', 'max:255'],
            'branding.care_email' => ['nullable', 'email', 'max:255'],
            'branding.phone' => ['nullable', 'string', 'max:40'],
            'branding.header_logo' => ['nullable', 'image', 'max:5120'],
            'branding.footer_logo' => ['nullable', 'image', 'max:5120'],
            'branding.remove_header_logo' => ['sometimes', 'boolean'],
            'branding.remove_footer_logo' => ['sometimes', 'boolean'],

            'social.instagram' => ['nullable', 'url', 'max:2048'],
            'social.facebook' => ['nullable', 'url', 'max:2048'],
            'social.tiktok' => ['nullable', 'url', 'max:2048'],
            'social.youtube' => ['nullable', 'url', 'max:2048'],

            'footer.blurb' => ['nullable', 'string', 'max:1000'],
            'footer.copyright_tagline' => ['nullable', 'string', 'max:255'],
            'footer.care_links' => ['nullable', 'array', 'max:8'],
            'footer.care_links.*.label' => ['required_with:footer.care_links', 'string', 'max:120'],
            'footer.care_links.*.type' => ['required_with:footer.care_links', 'string', Rule::in(['page', 'url', 'route'])],
            'footer.care_links.*.value' => ['required_with:footer.care_links', 'string', 'max:255'],
            'footer.about_links' => ['nullable', 'array', 'max:8'],
            'footer.about_links.*.label' => ['required_with:footer.about_links', 'string', 'max:120'],
            'footer.about_links.*.type' => ['required_with:footer.about_links', 'string', Rule::in(['page', 'url', 'route'])],
            'footer.about_links.*.value' => ['required_with:footer.about_links', 'string', 'max:255'],

            'homepage.hero.eyebrow' => ['nullable', 'string', 'max:255'],
            'homepage.hero.headline' => ['nullable', 'string', 'max:500'],
            'homepage.hero.body' => ['nullable', 'string', 'max:2000'],
            'homepage.hero.primary_cta_label' => ['nullable', 'string', 'max:120'],
            'homepage.hero.primary_cta_url' => ['nullable', 'string', 'max:2048'],
            'homepage.hero.secondary_cta_label' => ['nullable', 'string', 'max:120'],
            'homepage.hero.secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            'homepage.banner_url' => ['nullable', 'url', 'max:2048'],
            'homepage.categories_eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.categories_heading' => ['nullable', 'string', 'max:255'],
            'homepage.featured_eyebrow' => ['nullable', 'string', 'max:120'],
            'homepage.featured_heading' => ['nullable', 'string', 'max:255'],
            'homepage.craft_steps' => ['nullable', 'array', 'size:3'],
            'homepage.craft_steps.*.title' => ['nullable', 'string', 'max:120'],
            'homepage.craft_steps.*.heading' => ['nullable', 'string', 'max:255'],
            'homepage.craft_steps.*.body' => ['nullable', 'string', 'max:1000'],

            'seo.default_description' => ['nullable', 'string', 'max:500'],
            'seo.title_suffix' => ['nullable', 'string', 'max:120'],
            'seo.og_image' => ['nullable', 'image', 'max:5120'],
            'seo.remove_og_image' => ['sometimes', 'boolean'],

            'auth.quote' => ['nullable', 'string', 'max:500'],
            'auth.attribution' => ['nullable', 'string', 'max:255'],

            'currency' => ['required', 'string', 'size:3'],
            'tax_mode' => ['required', 'string', Rule::in(['inclusive', 'exclusive', 'none'])],
            'featured_collection_id' => ['nullable', 'integer', 'exists:collections,id'],
        ];
    }
}
