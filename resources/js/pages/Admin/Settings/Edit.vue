<script setup lang="ts">
import ImageDropzone from '@/components/Admin/ImageDropzone.vue';
import LinkRepeaterRow from '@/components/Admin/LinkRepeaterRow.vue';
import SettingsCard from '@/components/Admin/SettingsCard.vue';
import SettingsNav from '@/components/Admin/SettingsNav.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

type SectionId = 'branding' | 'social' | 'footer' | 'homepage' | 'seo' | 'auth' | 'commerce';

const props = defineProps<{
    content: StoreContent;
    collections: Collection[];
    taxModes: TaxMode[];
}>();

const navItems: { id: SectionId; label: string }[] = [
    { id: 'branding', label: 'Branding' },
    { id: 'social', label: 'Social Links' },
    { id: 'footer', label: 'Footer' },
    { id: 'homepage', label: 'Homepage' },
    { id: 'seo', label: 'SEO Defaults' },
    { id: 'auth', label: 'Auth Panel' },
    { id: 'commerce', label: 'Commerce' },
];

const sectionMeta: Record<SectionId, { title: string; intro: string }> = {
    branding: {
        title: 'Branding',
        intro: 'Your store name, logo, and contact details — shown in the header, footer, and order emails.',
    },
    social: {
        title: 'Social Links',
        intro: 'Links shown as icons in your footer. Leave blank to hide a platform.',
    },
    footer: {
        title: 'Footer',
        intro: "Footer content and the link columns shown on every storefront page.",
    },
    homepage: {
        title: 'Homepage',
        intro: "Content shown on your storefront's homepage — hero, category tiles, and the craft/process section.",
    },
    seo: {
        title: 'SEO Defaults',
        intro: "Default metadata used when a page doesn't define its own title or description.",
    },
    auth: {
        title: 'Auth Panel',
        intro: "Shown on the login, register, and password reset pages' brand panel.",
    },
    commerce: {
        title: 'Commerce',
        intro: 'Core store behavior — currency, tax handling, and your default featured collection.',
    },
};

const activeSection = ref<SectionId>('branding');
const dragFromIndex = ref<number | null>(null);
const dragGroup = ref<'care_links' | 'about_links' | null>(null);
let removeBeforeUnload: (() => void) | null = null;
let removeInertiaBefore: (() => void) | null = null;

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
        care_links: props.content.footer.care_links.map((link) => ({ ...link })),
        about_links: props.content.footer.about_links.map((link) => ({ ...link })),
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

const currentSection = computed(() => sectionMeta[activeSection.value]);
const formErrors = computed(() => form.errors as Record<string, string | undefined>);
const headerLogoUrl = computed(() =>
    props.content.branding.header_logo_path ? storageUrl(props.content.branding.header_logo_path) : null,
);
const footerLogoUrl = computed(() =>
    props.content.branding.footer_logo_path ? storageUrl(props.content.branding.footer_logo_path) : null,
);
const ogImageUrl = computed(() => (props.content.seo.og_image_path ? storageUrl(props.content.seo.og_image_path) : null));

const fieldClass =
    'flex h-9 w-full rounded-md border border-stone-300 bg-white px-3 text-[13.5px] outline-none transition focus:border-[#8C5A2B] focus:ring-2 focus:ring-[#8C5A2B]/20';
const textareaClass =
    'flex min-h-16 w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-[13.5px] outline-none transition focus:border-[#8C5A2B] focus:ring-2 focus:ring-[#8C5A2B]/20';

function selectSection(id: string) {
    activeSection.value = id as SectionId;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function sectionForErrorKey(key: string): SectionId | null {
    if (key.startsWith('branding.')) {
        return 'branding';
    }
    if (key.startsWith('social.')) {
        return 'social';
    }
    if (key.startsWith('footer.')) {
        return 'footer';
    }
    if (key.startsWith('homepage.')) {
        return 'homepage';
    }
    if (key.startsWith('seo.')) {
        return 'seo';
    }
    if (key.startsWith('auth.')) {
        return 'auth';
    }
    if (key === 'currency' || key === 'tax_mode' || key === 'featured_collection_id') {
        return 'commerce';
    }

    return null;
}

function focusFirstErrorSection() {
    const firstKey = Object.keys(form.errors)[0];

    if (!firstKey) {
        return;
    }

    const section = sectionForErrorKey(firstKey);

    if (section) {
        activeSection.value = section;
    }
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.settings.update'), {
        forceFormData: true,
        onSuccess: () => {
            form.branding.header_logo = null;
            form.branding.footer_logo = null;
            form.seo.og_image = null;
            form.branding.remove_header_logo = false;
            form.branding.remove_footer_logo = false;
            form.seo.remove_og_image = false;
            form.defaults();
        },
        onError: () => {
            focusFirstErrorSection();
        },
    });
}

function addFooterLink(group: 'care_links' | 'about_links') {
    form.footer[group].push({ label: '', type: 'page', value: '' });
}

function removeFooterLink(group: 'care_links' | 'about_links', index: number) {
    form.footer[group].splice(index, 1);
}

function onLinkDragStart(group: 'care_links' | 'about_links', index: number) {
    dragGroup.value = group;
    dragFromIndex.value = index;
}

function onLinkDrop(group: 'care_links' | 'about_links', toIndex: number) {
    if (dragGroup.value !== group || dragFromIndex.value === null || dragFromIndex.value === toIndex) {
        dragFromIndex.value = null;
        dragGroup.value = null;
        return;
    }

    const links = form.footer[group];
    const [moved] = links.splice(dragFromIndex.value, 1);

    if (moved) {
        links.splice(toIndex, 0, moved);
    }

    dragFromIndex.value = null;
    dragGroup.value = null;
}

function confirmLeave(): boolean {
    if (!form.isDirty) {
        return true;
    }

    return window.confirm('You have unsaved changes. Leave this page?');
}

function onBeforeUnload(event: BeforeUnloadEvent) {
    if (!form.isDirty) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

onMounted(() => {
    focusFirstErrorSection();

    window.addEventListener('beforeunload', onBeforeUnload);
    removeBeforeUnload = () => window.removeEventListener('beforeunload', onBeforeUnload);

    removeInertiaBefore = router.on('before', (event) => {
        const visit = event.detail.visit;
        const isSettingsSave =
            visit.url.pathname.includes('/admin/settings') && ['post', 'put'].includes(visit.method);

        if (isSettingsSave || confirmLeave()) {
            return;
        }

        event.preventDefault();
    });
});

onBeforeUnmount(() => {
    removeBeforeUnload?.();
    removeInertiaBefore?.();
});

watch(
    () => form.errors,
    () => {
        if (Object.keys(form.errors).length > 0) {
            focusFirstErrorSection();
        }
    },
);
</script>

<template>
    <Head title="Settings" />

    <AdminLayout>
        <form class="-m-4 flex min-h-[calc(100vh-3.5rem)] flex-col bg-[#F9F8F6] lg:-m-6" @submit.prevent="submit">
            <div class="grid flex-1 lg:grid-cols-[240px_1fr]">
                <SettingsNav :items="navItems" :active-id="activeSection" @select="selectSection" />

                <div class="flex min-w-0 flex-col">
                    <div
                        class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-stone-200 bg-white px-6 py-4 lg:px-10"
                    >
                        <div>
                            <h1 class="text-lg font-semibold text-stone-900">{{ currentSection.title }}</h1>
                            <p class="mt-0.5 text-[12.5px] text-stone-500">Store settings</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span v-if="form.isDirty" class="inline-flex items-center gap-1.5 text-[12.5px] text-stone-500">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-900" />
                                Unsaved changes
                            </span>
                            <button
                                type="submit"
                                class="rounded-md bg-teal-900 px-5 py-2 text-[13.5px] font-semibold text-white transition-colors hover:bg-teal-800 disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </div>

                    <div class="max-w-[760px] px-6 py-8 pb-24 lg:px-10">
                        <p class="mb-7 max-w-[520px] text-[13.5px] leading-5 text-stone-500">{{ currentSection.intro }}</p>

                        <!-- Branding -->
                        <div v-show="activeSection === 'branding'" class="space-y-0">
                            <SettingsCard title="Store identity">
                                <div class="mb-[18px]">
                                    <Label for="branding_name" class="mb-1.5 text-[12.5px] font-semibold">Store name</Label>
                                    <Input id="branding_name" v-model="form.branding.name" required :class="fieldClass" />
                                    <InputError :message="formErrors['branding.name']" />
                                </div>
                                <div class="mb-[18px] grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="branding_tagline" class="mb-1.5 text-[12.5px] font-semibold">Tagline</Label>
                                        <Input id="branding_tagline" v-model="form.branding.tagline" :class="fieldClass" />
                                    </div>
                                    <div>
                                        <Label for="branding_location" class="mb-1.5 text-[12.5px] font-semibold">Location</Label>
                                        <Input id="branding_location" v-model="form.branding.location" :class="fieldClass" />
                                    </div>
                                </div>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="branding_care_email" class="mb-1.5 text-[12.5px] font-semibold">Care email</Label>
                                        <Input
                                            id="branding_care_email"
                                            v-model="form.branding.care_email"
                                            type="email"
                                            :class="fieldClass"
                                        />
                                        <p class="mt-1.5 text-[11.5px] leading-4 text-stone-500">
                                            Shown in footer and order emails as the support contact.
                                        </p>
                                        <InputError :message="formErrors['branding.care_email']" />
                                    </div>
                                    <div>
                                        <Label for="branding_phone" class="mb-1.5 text-[12.5px] font-semibold">
                                            Phone <span class="font-normal text-stone-500">(optional)</span>
                                        </Label>
                                        <Input
                                            id="branding_phone"
                                            v-model="form.branding.phone"
                                            placeholder="+92 300 0000000"
                                            :class="fieldClass"
                                        />
                                    </div>
                                </div>
                            </SettingsCard>

                            <SettingsCard title="Logos" description="PNG or SVG recommended, transparent background.">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">Header logo</Label>
                                        <ImageDropzone
                                            v-model="form.branding.header_logo"
                                            v-model:remove="form.branding.remove_header_logo"
                                            :existing-url="headerLogoUrl"
                                        />
                                        <InputError :message="formErrors['branding.header_logo']" />
                                    </div>
                                    <div>
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">Footer logo</Label>
                                        <ImageDropzone
                                            v-model="form.branding.footer_logo"
                                            v-model:remove="form.branding.remove_footer_logo"
                                            :existing-url="footerLogoUrl"
                                        />
                                        <InputError :message="formErrors['branding.footer_logo']" />
                                    </div>
                                </div>
                            </SettingsCard>
                        </div>

                        <!-- Social -->
                        <div v-show="activeSection === 'social'">
                            <SettingsCard title="Platforms">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="social_instagram" class="mb-1.5 text-[12.5px] font-semibold">Instagram</Label>
                                        <Input
                                            id="social_instagram"
                                            v-model="form.social.instagram"
                                            type="url"
                                            placeholder="https://instagram.com/yourstore"
                                            :class="fieldClass"
                                        />
                                    </div>
                                    <div>
                                        <Label for="social_facebook" class="mb-1.5 text-[12.5px] font-semibold">Facebook</Label>
                                        <Input
                                            id="social_facebook"
                                            v-model="form.social.facebook"
                                            type="url"
                                            placeholder="https://facebook.com/yourstore"
                                            :class="fieldClass"
                                        />
                                    </div>
                                    <div>
                                        <Label for="social_tiktok" class="mb-1.5 text-[12.5px] font-semibold">TikTok</Label>
                                        <Input
                                            id="social_tiktok"
                                            v-model="form.social.tiktok"
                                            type="url"
                                            placeholder="https://tiktok.com/@yourstore"
                                            :class="fieldClass"
                                        />
                                    </div>
                                    <div>
                                        <Label for="social_youtube" class="mb-1.5 text-[12.5px] font-semibold">YouTube</Label>
                                        <Input
                                            id="social_youtube"
                                            v-model="form.social.youtube"
                                            type="url"
                                            placeholder="https://youtube.com/@yourstore"
                                            :class="fieldClass"
                                        />
                                    </div>
                                </div>
                            </SettingsCard>
                        </div>

                        <!-- Footer -->
                        <div v-show="activeSection === 'footer'">
                            <SettingsCard title="Footer text">
                                <div class="mb-[18px]">
                                    <Label for="footer_blurb" class="mb-1.5 text-[12.5px] font-semibold">Brand blurb</Label>
                                    <textarea id="footer_blurb" v-model="form.footer.blurb" rows="3" :class="textareaClass" />
                                </div>
                                <div>
                                    <Label for="footer_copyright_tagline" class="mb-1.5 text-[12.5px] font-semibold">
                                        Copyright tagline
                                    </Label>
                                    <Input id="footer_copyright_tagline" v-model="form.footer.copyright_tagline" :class="fieldClass" />
                                </div>
                            </SettingsCard>

                            <SettingsCard title="Care & Returns links" description="Shown in the footer's second column.">
                                <LinkRepeaterRow
                                    v-for="(link, index) in form.footer.care_links"
                                    :key="`care-${index}`"
                                    :link="link"
                                    :index="index"
                                    @remove="removeFooterLink('care_links', index)"
                                    @drag-start="onLinkDragStart('care_links', $event)"
                                    @drop="onLinkDrop('care_links', $event)"
                                />
                                <button
                                    type="button"
                                    class="mt-3 flex items-center gap-1 text-[12.5px] font-semibold text-[#7A4B22] hover:underline"
                                    @click="addFooterLink('care_links')"
                                >
                                    + Add link
                                </button>
                            </SettingsCard>

                            <SettingsCard title="About links" description="Shown in the footer's third column.">
                                <LinkRepeaterRow
                                    v-for="(link, index) in form.footer.about_links"
                                    :key="`about-${index}`"
                                    :link="link"
                                    :index="index"
                                    @remove="removeFooterLink('about_links', index)"
                                    @drag-start="onLinkDragStart('about_links', $event)"
                                    @drop="onLinkDrop('about_links', $event)"
                                />
                                <button
                                    type="button"
                                    class="mt-3 flex items-center gap-1 text-[12.5px] font-semibold text-[#7A4B22] hover:underline"
                                    @click="addFooterLink('about_links')"
                                >
                                    + Add link
                                </button>
                            </SettingsCard>
                        </div>

                        <!-- Homepage -->
                        <div v-show="activeSection === 'homepage'">
                            <SettingsCard title="Hero section" description="The large banner at the top of your homepage.">
                                <div class="mb-[18px]">
                                    <Label for="hero_eyebrow" class="mb-1.5 text-[12.5px] font-semibold">Eyebrow text</Label>
                                    <Input id="hero_eyebrow" v-model="form.homepage.hero.eyebrow" :class="fieldClass" />
                                </div>
                                <div class="mb-[18px]">
                                    <Label for="hero_headline" class="mb-1.5 text-[12.5px] font-semibold">Headline</Label>
                                    <textarea id="hero_headline" v-model="form.homepage.hero.headline" rows="2" :class="textareaClass" />
                                </div>
                                <div class="mb-[18px]">
                                    <Label for="hero_body" class="mb-1.5 text-[12.5px] font-semibold">Body text</Label>
                                    <textarea id="hero_body" v-model="form.homepage.hero.body" rows="3" :class="textareaClass" />
                                </div>
                                <div class="mb-[18px] grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="primary_cta_label" class="mb-1.5 text-[12.5px] font-semibold">Primary CTA label</Label>
                                        <Input id="primary_cta_label" v-model="form.homepage.hero.primary_cta_label" :class="fieldClass" />
                                    </div>
                                    <div>
                                        <Label for="primary_cta_url" class="mb-1.5 text-[12.5px] font-semibold">Primary CTA URL</Label>
                                        <Input id="primary_cta_url" v-model="form.homepage.hero.primary_cta_url" :class="fieldClass" />
                                    </div>
                                </div>
                                <div class="mb-[18px] grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="secondary_cta_label" class="mb-1.5 text-[12.5px] font-semibold">
                                            Secondary CTA label
                                        </Label>
                                        <Input
                                            id="secondary_cta_label"
                                            v-model="form.homepage.hero.secondary_cta_label"
                                            :class="fieldClass"
                                        />
                                    </div>
                                    <div>
                                        <Label for="secondary_cta_url" class="mb-1.5 text-[12.5px] font-semibold">Secondary CTA URL</Label>
                                        <Input id="secondary_cta_url" v-model="form.homepage.hero.secondary_cta_url" :class="fieldClass" />
                                    </div>
                                </div>
                                <div>
                                    <Label for="homepage_banner_url" class="mb-1.5 text-[12.5px] font-semibold">Banner URL</Label>
                                    <Input
                                        id="homepage_banner_url"
                                        v-model="form.homepage.banner_url"
                                        type="url"
                                        :class="fieldClass"
                                    />
                                    <p class="mt-1.5 text-[11.5px] leading-4 text-stone-500">
                                        Optional promotional link associated with the homepage.
                                    </p>
                                </div>
                            </SettingsCard>

                            <SettingsCard title="Category tiles">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="categories_eyebrow" class="mb-1.5 text-[12.5px] font-semibold">Eyebrow</Label>
                                        <Input id="categories_eyebrow" v-model="form.homepage.categories_eyebrow" :class="fieldClass" />
                                    </div>
                                    <div>
                                        <Label for="categories_heading" class="mb-1.5 text-[12.5px] font-semibold">Heading</Label>
                                        <Input id="categories_heading" v-model="form.homepage.categories_heading" :class="fieldClass" />
                                    </div>
                                </div>
                            </SettingsCard>

                            <SettingsCard title="Featured collection">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="featured_eyebrow" class="mb-1.5 text-[12.5px] font-semibold">Eyebrow</Label>
                                        <Input id="featured_eyebrow" v-model="form.homepage.featured_eyebrow" :class="fieldClass" />
                                    </div>
                                    <div>
                                        <Label for="featured_heading" class="mb-1.5 text-[12.5px] font-semibold">Heading</Label>
                                        <Input id="featured_heading" v-model="form.homepage.featured_heading" :class="fieldClass" />
                                    </div>
                                </div>
                            </SettingsCard>

                            <SettingsCard title="Craft / process steps" description="The three-step &quot;how it's made&quot; section.">
                                <div
                                    v-for="(step, index) in form.homepage.craft_steps"
                                    :key="`craft-${index}`"
                                    class="mb-3 rounded-lg border border-stone-200 bg-stone-50 p-4 last:mb-0"
                                >
                                    <div class="mb-2.5 text-xs font-semibold text-stone-500">Step {{ index + 1 }}</div>
                                    <div class="mb-3">
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">Label</Label>
                                        <Input v-model="step.title" :class="fieldClass" />
                                    </div>
                                    <div class="mb-3">
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">Title</Label>
                                        <Input v-model="step.heading" :class="fieldClass" />
                                    </div>
                                    <div>
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">Description</Label>
                                        <textarea v-model="step.body" rows="2" :class="textareaClass" />
                                    </div>
                                </div>
                            </SettingsCard>
                        </div>

                        <!-- SEO -->
                        <div v-show="activeSection === 'seo'">
                            <SettingsCard title="Defaults">
                                <div class="mb-[18px]">
                                    <Label for="seo_default_description" class="mb-1.5 text-[12.5px] font-semibold">
                                        Default meta description
                                    </Label>
                                    <textarea
                                        id="seo_default_description"
                                        v-model="form.seo.default_description"
                                        rows="2"
                                        :class="textareaClass"
                                    />
                                </div>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="seo_title_suffix" class="mb-1.5 text-[12.5px] font-semibold">Title suffix</Label>
                                        <Input id="seo_title_suffix" v-model="form.seo.title_suffix" :class="fieldClass" />
                                        <p class="mt-1.5 text-[11.5px] leading-4 text-stone-500">
                                            Appended to every page title, e.g. "Foundry Bifold — {{ form.seo.title_suffix || 'Store' }}"
                                        </p>
                                    </div>
                                    <div>
                                        <Label class="mb-1.5 text-[12.5px] font-semibold">OG image</Label>
                                        <ImageDropzone
                                            v-model="form.seo.og_image"
                                            v-model:remove="form.seo.remove_og_image"
                                            :existing-url="ogImageUrl"
                                            hint="1200×630px recommended"
                                        />
                                        <InputError :message="formErrors['seo.og_image']" />
                                    </div>
                                </div>
                            </SettingsCard>
                        </div>

                        <!-- Auth -->
                        <div v-show="activeSection === 'auth'">
                            <SettingsCard title="Brand panel copy">
                                <div class="mb-[18px]">
                                    <Label for="auth_quote" class="mb-1.5 text-[12.5px] font-semibold">Quote</Label>
                                    <textarea id="auth_quote" v-model="form.auth.quote" rows="2" :class="textareaClass" />
                                </div>
                                <div>
                                    <Label for="auth_attribution" class="mb-1.5 text-[12.5px] font-semibold">Attribution</Label>
                                    <Input id="auth_attribution" v-model="form.auth.attribution" :class="fieldClass" />
                                </div>
                            </SettingsCard>
                        </div>

                        <!-- Commerce -->
                        <div v-show="activeSection === 'commerce'">
                            <SettingsCard title="Store behavior">
                                <div class="mb-[18px] grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label for="featured_collection_id" class="mb-1.5 text-[12.5px] font-semibold">
                                            Featured collection
                                        </Label>
                                        <select
                                            id="featured_collection_id"
                                            v-model="form.featured_collection_id"
                                            :class="fieldClass"
                                        >
                                            <option :value="null">None (use featured flag)</option>
                                            <option
                                                v-for="collection in collections"
                                                :key="collection.id"
                                                :value="collection.id"
                                            >
                                                {{ collection.name }}
                                            </option>
                                        </select>
                                        <p class="mt-1.5 text-[11.5px] leading-4 text-stone-500">
                                            Shown in the homepage's featured product grid.
                                        </p>
                                    </div>
                                    <div>
                                        <Label for="currency" class="mb-1.5 text-[12.5px] font-semibold">Currency</Label>
                                        <Input id="currency" v-model="form.currency" maxlength="3" required :class="fieldClass" />
                                        <InputError :message="form.errors.currency" />
                                    </div>
                                </div>
                                <div>
                                    <Label for="tax_mode" class="mb-1.5 text-[12.5px] font-semibold">Tax mode</Label>
                                    <select id="tax_mode" v-model="form.tax_mode" :class="fieldClass">
                                        <option v-for="mode in taxModes" :key="mode.value" :value="mode.value">
                                            {{ mode.label }}
                                        </option>
                                    </select>
                                    <p class="mt-1.5 text-[11.5px] leading-4 text-stone-500">
                                        Exclusive: tax is added on top of listed prices at checkout. Inclusive: listed prices already
                                        include tax.
                                    </p>
                                    <InputError :message="form.errors.tax_mode" />
                                </div>
                            </SettingsCard>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
