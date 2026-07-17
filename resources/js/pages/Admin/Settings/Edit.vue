<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

interface FooterLink {
    label: string;
    type: 'page' | 'url' | 'route';
    value: string;
}

interface CraftStep {
    title: string;
    heading: string;
    body: string;
}

interface StoreContent {
    branding: {
        name: string;
        tagline: string | null;
        location: string | null;
        care_email: string | null;
        phone: string | null;
        header_logo_path?: string | null;
        footer_logo_path?: string | null;
        header_logo_url?: string | null;
        footer_logo_url?: string | null;
    };
    social: {
        instagram: string | null;
        facebook: string | null;
        tiktok: string | null;
        youtube: string | null;
    };
    footer: {
        blurb: string;
        copyright_tagline: string;
        care_links: FooterLink[];
        about_links: FooterLink[];
    };
    homepage: {
        hero: {
            eyebrow: string;
            headline: string;
            body: string;
            primary_cta_label: string;
            primary_cta_url: string;
            secondary_cta_label: string;
            secondary_cta_url: string;
        };
        banner_url: string | null;
        categories_eyebrow: string;
        categories_heading: string;
        featured_eyebrow: string;
        featured_heading: string;
        craft_steps: CraftStep[];
    };
    seo: {
        default_description: string;
        title_suffix: string;
        og_image_path?: string | null;
        og_image_url?: string | null;
    };
    auth: {
        quote: string;
        attribution: string;
    };
    currency: string;
    tax_mode: string;
    featured_collection_id: number | null;
}

interface Collection {
    id: number;
    name: string;
}

interface TaxMode {
    value: string;
    label: string;
}

const props = defineProps<{
    content: StoreContent;
    collections: Collection[];
    taxModes: TaxMode[];
}>();

const form = useForm({
    branding: {
        name: props.content.branding.name,
        tagline: props.content.branding.tagline ?? '',
        location: props.content.branding.location ?? '',
        care_email: props.content.branding.care_email ?? '',
        phone: props.content.branding.phone ?? '',
        header_logo: null as File | null,
        footer_logo: null as File | null,
        remove_header_logo: false,
        remove_footer_logo: false,
    },
    social: {
        instagram: props.content.social.instagram ?? '',
        facebook: props.content.social.facebook ?? '',
        tiktok: props.content.social.tiktok ?? '',
        youtube: props.content.social.youtube ?? '',
    },
    footer: {
        blurb: props.content.footer.blurb,
        copyright_tagline: props.content.footer.copyright_tagline,
        care_links: [...props.content.footer.care_links],
        about_links: [...props.content.footer.about_links],
    },
    homepage: {
        hero: { ...props.content.homepage.hero },
        banner_url: props.content.homepage.banner_url ?? '',
        categories_eyebrow: props.content.homepage.categories_eyebrow,
        categories_heading: props.content.homepage.categories_heading,
        featured_eyebrow: props.content.homepage.featured_eyebrow,
        featured_heading: props.content.homepage.featured_heading,
        craft_steps: props.content.homepage.craft_steps.map((step) => ({ ...step })),
    },
    seo: {
        default_description: props.content.seo.default_description,
        title_suffix: props.content.seo.title_suffix,
        og_image: null as File | null,
        remove_og_image: false,
    },
    auth: { ...props.content.auth },
    currency: props.content.currency,
    tax_mode: props.content.tax_mode,
    featured_collection_id: props.content.featured_collection_id,
});

const headerLogoPreview = ref<string | null>(null);
const footerLogoPreview = ref<string | null>(null);
const ogImagePreview = ref<string | null>(null);

const currentHeaderLogoUrl = computed(() => {
    if (form.branding.remove_header_logo || !props.content.branding.header_logo_path) {
        return null;
    }

    return storageUrl(props.content.branding.header_logo_path);
});

const currentFooterLogoUrl = computed(() => {
    if (form.branding.remove_footer_logo || !props.content.branding.footer_logo_path) {
        return null;
    }

    return storageUrl(props.content.branding.footer_logo_path);
});

const currentOgImageUrl = computed(() => {
    if (form.seo.remove_og_image || !props.content.seo.og_image_path) {
        return null;
    }

    return storageUrl(props.content.seo.og_image_path);
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.settings.update'), {
        forceFormData: true,
        onSuccess: () => {
            form.branding.header_logo = null;
            form.branding.footer_logo = null;
            form.seo.og_image = null;
            clearImagePreview(headerLogoPreview);
            clearImagePreview(footerLogoPreview);
            clearImagePreview(ogImagePreview);
        },
    });
}

function onImageSelected(event: Event, field: 'header_logo' | 'footer_logo' | 'og_image') {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (field === 'header_logo') {
        form.branding.header_logo = file;
        form.branding.remove_header_logo = false;
        setPreview(file, headerLogoPreview);
    } else if (field === 'footer_logo') {
        form.branding.footer_logo = file;
        form.branding.remove_footer_logo = false;
        setPreview(file, footerLogoPreview);
    } else {
        form.seo.og_image = file;
        form.seo.remove_og_image = false;
        setPreview(file, ogImagePreview);
    }
}

function setPreview(file: File | null, previewRef: { value: string | null }) {
    clearImagePreview(previewRef);

    if (file) {
        previewRef.value = URL.createObjectURL(file);
    }
}

function clearImagePreview(previewRef: { value: string | null }) {
    if (previewRef.value) {
        URL.revokeObjectURL(previewRef.value);
        previewRef.value = null;
    }
}

function addFooterLink(group: 'care_links' | 'about_links') {
    form.footer[group].push({ label: '', type: 'page', value: '' });
}

function removeFooterLink(group: 'care_links' | 'about_links', index: number) {
    form.footer[group].splice(index, 1);
}

onBeforeUnmount(() => {
    clearImagePreview(headerLogoPreview);
    clearImagePreview(footerLogoPreview);
    clearImagePreview(ogImagePreview);
});
</script>

<template>
    <Head title="Settings" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Store settings</h1>

        <form class="max-w-3xl space-y-10" @submit.prevent="submit">
            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">Branding</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="branding_name">Store name</Label>
                        <Input id="branding_name" v-model="form.branding.name" required />
                        <InputError :message="form.errors['branding.name']" />
                    </div>
                    <div class="space-y-2">
                        <Label for="branding_tagline">Tagline</Label>
                        <Input id="branding_tagline" v-model="form.branding.tagline" />
                    </div>
                    <div class="space-y-2">
                        <Label for="branding_location">Location</Label>
                        <Input id="branding_location" v-model="form.branding.location" />
                    </div>
                    <div class="space-y-2">
                        <Label for="branding_care_email">Care email</Label>
                        <Input id="branding_care_email" v-model="form.branding.care_email" type="email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="branding_phone">Phone</Label>
                        <Input id="branding_phone" v-model="form.branding.phone" />
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Header logo</Label>
                        <img
                            v-if="headerLogoPreview || currentHeaderLogoUrl"
                            :src="headerLogoPreview ?? currentHeaderLogoUrl ?? ''"
                            alt="Header logo preview"
                            class="h-16 w-auto object-contain"
                        />
                        <Input type="file" accept="image/*" @change="onImageSelected($event, 'header_logo')" />
                        <label v-if="currentHeaderLogoUrl" class="flex items-center gap-2 text-sm">
                            <input v-model="form.branding.remove_header_logo" type="checkbox" />
                            Remove header logo
                        </label>
                    </div>
                    <div class="space-y-2">
                        <Label>Footer logo</Label>
                        <img
                            v-if="footerLogoPreview || currentFooterLogoUrl"
                            :src="footerLogoPreview ?? currentFooterLogoUrl ?? ''"
                            alt="Footer logo preview"
                            class="h-16 w-auto object-contain"
                        />
                        <Input type="file" accept="image/*" @change="onImageSelected($event, 'footer_logo')" />
                        <label v-if="currentFooterLogoUrl" class="flex items-center gap-2 text-sm">
                            <input v-model="form.branding.remove_footer_logo" type="checkbox" />
                            Remove footer logo
                        </label>
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">Social links</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="social_instagram">Instagram</Label>
                        <Input id="social_instagram" v-model="form.social.instagram" type="url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="social_facebook">Facebook</Label>
                        <Input id="social_facebook" v-model="form.social.facebook" type="url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="social_tiktok">TikTok</Label>
                        <Input id="social_tiktok" v-model="form.social.tiktok" type="url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="social_youtube">YouTube</Label>
                        <Input id="social_youtube" v-model="form.social.youtube" type="url" />
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">Footer</h2>
                <div class="space-y-2">
                    <Label for="footer_blurb">Brand blurb</Label>
                    <textarea
                        id="footer_blurb"
                        v-model="form.footer.blurb"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="footer_copyright_tagline">Copyright tagline</Label>
                    <Input id="footer_copyright_tagline" v-model="form.footer.copyright_tagline" />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium">Care &amp; returns links</h3>
                        <Button type="button" variant="outline" size="sm" @click="addFooterLink('care_links')">Add link</Button>
                    </div>
                    <div
                        v-for="(link, index) in form.footer.care_links"
                        :key="`care-${index}`"
                        class="grid gap-2 rounded-md border border-border p-3 sm:grid-cols-4"
                    >
                        <Input v-model="link.label" placeholder="Label" />
                        <select v-model="link.type" class="flex h-9 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="page">CMS page</option>
                            <option value="url">URL</option>
                            <option value="route">Route name</option>
                        </select>
                        <Input v-model="link.value" placeholder="Slug, URL, or route" />
                        <Button type="button" variant="outline" size="sm" @click="removeFooterLink('care_links', index)">Remove</Button>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium">About links</h3>
                        <Button type="button" variant="outline" size="sm" @click="addFooterLink('about_links')">Add link</Button>
                    </div>
                    <div
                        v-for="(link, index) in form.footer.about_links"
                        :key="`about-${index}`"
                        class="grid gap-2 rounded-md border border-border p-3 sm:grid-cols-4"
                    >
                        <Input v-model="link.label" placeholder="Label" />
                        <select v-model="link.type" class="flex h-9 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="page">CMS page</option>
                            <option value="url">URL</option>
                            <option value="route">Route name</option>
                        </select>
                        <Input v-model="link.value" placeholder="Slug, URL, or route" />
                        <Button type="button" variant="outline" size="sm" @click="removeFooterLink('about_links', index)">Remove</Button>
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">Homepage</h2>
                <div class="space-y-2">
                    <Label for="hero_eyebrow">Hero eyebrow</Label>
                    <Input id="hero_eyebrow" v-model="form.homepage.hero.eyebrow" />
                </div>
                <div class="space-y-2">
                    <Label for="hero_headline">Hero headline</Label>
                    <textarea
                        id="hero_headline"
                        v-model="form.homepage.hero.headline"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="hero_body">Hero body</Label>
                    <textarea
                        id="hero_body"
                        v-model="form.homepage.hero.body"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="primary_cta_label">Primary CTA label</Label>
                        <Input id="primary_cta_label" v-model="form.homepage.hero.primary_cta_label" />
                    </div>
                    <div class="space-y-2">
                        <Label for="primary_cta_url">Primary CTA URL</Label>
                        <Input id="primary_cta_url" v-model="form.homepage.hero.primary_cta_url" />
                    </div>
                    <div class="space-y-2">
                        <Label for="secondary_cta_label">Secondary CTA label</Label>
                        <Input id="secondary_cta_label" v-model="form.homepage.hero.secondary_cta_label" />
                    </div>
                    <div class="space-y-2">
                        <Label for="secondary_cta_url">Secondary CTA URL</Label>
                        <Input id="secondary_cta_url" v-model="form.homepage.hero.secondary_cta_url" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="homepage_banner_url">Banner URL</Label>
                    <Input id="homepage_banner_url" v-model="form.homepage.banner_url" type="url" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="categories_eyebrow">Categories eyebrow</Label>
                        <Input id="categories_eyebrow" v-model="form.homepage.categories_eyebrow" />
                    </div>
                    <div class="space-y-2">
                        <Label for="categories_heading">Categories heading</Label>
                        <Input id="categories_heading" v-model="form.homepage.categories_heading" />
                    </div>
                    <div class="space-y-2">
                        <Label for="featured_eyebrow">Featured eyebrow</Label>
                        <Input id="featured_eyebrow" v-model="form.homepage.featured_eyebrow" />
                    </div>
                    <div class="space-y-2">
                        <Label for="featured_heading">Featured heading</Label>
                        <Input id="featured_heading" v-model="form.homepage.featured_heading" />
                    </div>
                </div>

                <div class="space-y-3">
                    <h3 class="text-sm font-medium">Craft strip</h3>
                    <div
                        v-for="(step, index) in form.homepage.craft_steps"
                        :key="`craft-${index}`"
                        class="space-y-2 rounded-md border border-border p-3"
                    >
                        <Input v-model="step.title" placeholder="Step title" />
                        <Input v-model="step.heading" placeholder="Heading" />
                        <textarea
                            v-model="step.body"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Body"
                        />
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">SEO defaults</h2>
                <div class="space-y-2">
                    <Label for="seo_default_description">Default meta description</Label>
                    <textarea
                        id="seo_default_description"
                        v-model="form.seo.default_description"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="seo_title_suffix">Title suffix</Label>
                    <Input id="seo_title_suffix" v-model="form.seo.title_suffix" />
                </div>
                <div class="space-y-2">
                    <Label>OG image</Label>
                    <img
                        v-if="ogImagePreview || currentOgImageUrl"
                        :src="ogImagePreview ?? currentOgImageUrl ?? ''"
                        alt="OG image preview"
                        class="h-24 w-auto object-contain"
                    />
                    <Input type="file" accept="image/*" @change="onImageSelected($event, 'og_image')" />
                    <label v-if="currentOgImageUrl" class="flex items-center gap-2 text-sm">
                        <input v-model="form.seo.remove_og_image" type="checkbox" />
                        Remove OG image
                    </label>
                </div>
            </section>

            <section class="space-y-4 border-b border-border pb-10">
                <h2 class="text-lg font-medium">Auth panel</h2>
                <div class="space-y-2">
                    <Label for="auth_quote">Quote</Label>
                    <textarea
                        id="auth_quote"
                        v-model="form.auth.quote"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="auth_attribution">Attribution</Label>
                    <Input id="auth_attribution" v-model="form.auth.attribution" />
                </div>
            </section>

            <section class="space-y-4">
                <h2 class="text-lg font-medium">Commerce</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="featured_collection_id">Featured collection</Label>
                        <select
                            id="featured_collection_id"
                            v-model="form.featured_collection_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option :value="null">None (use featured flag)</option>
                            <option v-for="collection in collections" :key="collection.id" :value="collection.id">
                                {{ collection.name }}
                            </option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="currency">Currency</Label>
                        <Input id="currency" v-model="form.currency" maxlength="3" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="tax_mode">Tax mode</Label>
                        <select id="tax_mode" v-model="form.tax_mode" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option v-for="mode in taxModes" :key="mode.value" :value="mode.value">{{ mode.label }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save settings</Button>
        </form>
    </AdminLayout>
</template>
