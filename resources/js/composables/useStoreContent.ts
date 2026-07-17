import { storageUrl } from '@/lib/storage';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface FooterLink {
    label: string;
    type: 'page' | 'url' | 'route';
    value: string;
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
        craft_steps: Array<{ title: string; heading: string; body: string }>;
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
}

export function resolveFooterLinkHref(link: FooterLink): string {
    if (link.type === 'page') {
        return route('pages.show', link.value);
    }

    if (link.type === 'route') {
        return route(link.value);
    }

    return link.value;
}

export function resolveCtaHref(url: string): string {
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('#') || url.startsWith('/')) {
        return url;
    }

    return `/${url.replace(/^\//, '')}`;
}

export function useStoreContent() {
    const page = usePage();

    const store = computed(() => page.props.store as StoreContent | undefined);

    const storeName = computed(() => store.value?.branding.name ?? (page.props.name as string) ?? 'Commerce');
    const headerLogoUrl = computed(() => {
        const path = store.value?.branding.header_logo_path;
        return path ? storageUrl(path) : store.value?.branding.header_logo_url ?? null;
    });
    const footerLogoUrl = computed(() => {
        const path = store.value?.branding.footer_logo_path;
        return path ? storageUrl(path) : store.value?.branding.footer_logo_url ?? null;
    });

    const socialLinks = computed(() => {
        const social = store.value?.social;
        if (!social) {
            return [];
        }

        return [
            { label: 'Instagram', url: social.instagram },
            { label: 'Facebook', url: social.facebook },
            { label: 'TikTok', url: social.tiktok },
            { label: 'YouTube', url: social.youtube },
        ].filter((item): item is { label: string; url: string } => Boolean(item.url));
    });

    return {
        store,
        storeName,
        headerLogoUrl,
        footerLogoUrl,
        socialLinks,
    };
}
