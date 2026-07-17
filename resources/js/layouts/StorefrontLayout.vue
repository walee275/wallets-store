<script setup lang="ts">
import StitchDivider from '@/components/Storefront/StitchDivider.vue';
import { resolveFooterLinkHref, useStoreContent } from '@/composables/useStoreContent';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface NavCategory {
    id: number;
    name: string;
    slug: string;
}

interface SearchResult {
    id: number;
    title: string;
    slug: string;
    price_cents: number;
    image: string | null;
}

const props = withDefaults(
    defineProps<{
        fullBleed?: boolean;
    }>(),
    {
        fullBleed: false,
    },
);

const page = usePage();
const { store, storeName, headerLogoUrl, footerLogoUrl, socialLinks } = useStoreContent();
const mobileMenuOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchOpen = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const navCategories = computed(() => (page.props.navCategories as NavCategory[]) ?? []);
const cartCount = computed(() => (page.props.cartCount as number) ?? 0);
const flash = computed(() => page.props.flash as { success?: string | null; error?: string | null });
const auth = computed(() => page.props.auth as { user?: { name: string } | null; isAdmin?: boolean });
const footerBlurb = computed(() => store.value?.footer.blurb ?? '');
const copyrightTagline = computed(() => store.value?.footer.copyright_tagline ?? '');
const location = computed(() => store.value?.branding.location ?? '');
const careEmail = computed(() => store.value?.branding.care_email ?? null);
const careLinks = computed(() => store.value?.footer.care_links ?? []);
const aboutLinks = computed(() => store.value?.footer.about_links ?? []);

function onSearchInput() {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    const q = searchQuery.value.trim();
    if (q.length < 2) {
        searchResults.value = [];
        searchOpen.value = false;
        return;
    }

    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`${route('search.autocomplete')}?q=${encodeURIComponent(q)}`);
            searchResults.value = await response.json();
            searchOpen.value = searchResults.value.length > 0;
        } catch {
            searchResults.value = [];
            searchOpen.value = false;
        }
    }, 250);
}

function closeSearch() {
    searchOpen.value = false;
    searchResults.value = [];
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-canvas font-sans text-ink">
        <header class="bg-espresso text-canvas">
            <div class="mx-auto flex max-w-[1240px] items-center justify-between gap-4 px-6 py-[26px] md:px-10">
                <button
                    type="button"
                    class="p-1 text-[#C9BEA8] md:hidden"
                    aria-label="Toggle menu"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                    <X v-else class="h-5 w-5" />
                </button>

                <Link :href="route('home')" class="flex items-center font-display text-[22px] font-[450] tracking-[0.5px] text-canvas">
                    <img v-if="headerLogoUrl" :src="headerLogoUrl" :alt="storeName" class="h-8 w-auto object-contain" />
                    <span v-else>{{ storeName }}</span>
                </Link>

                <nav class="ml-4 hidden items-center gap-9 text-sm tracking-[0.3px] md:flex">
                    <Link
                        v-for="category in navCategories"
                        :key="category.id"
                        :href="route('catalog.index', { category: category.slug })"
                        class="text-[#C9BEA8] transition-colors hover:text-brass"
                    >
                        {{ category.name }}
                    </Link>
                </nav>

                <div class="flex items-center gap-4 text-[13px] sm:gap-6">
                    <div class="relative hidden min-w-[200px] lg:block">
                        <div
                            class="flex items-center gap-2 border border-[#4A3A2C] px-3.5 py-2 text-[13px] text-[#8F8071]"
                        >
                            <Search class="h-3.5 w-3.5 shrink-0" />
                            <input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search the collection…"
                                class="w-full bg-transparent text-[13px] text-[#C9BEA8] outline-none placeholder:text-[#8F8071]"
                                @input="onSearchInput"
                                @focus="searchQuery.length >= 2 && searchResults.length && (searchOpen = true)"
                                @blur="setTimeout(closeSearch, 150)"
                            />
                        </div>
                        <div
                            v-if="searchOpen"
                            class="absolute left-0 right-0 top-full z-50 mt-1 border border-[#4A3A2C] bg-espresso"
                        >
                            <Link
                                v-for="result in searchResults"
                                :key="result.id"
                                :href="route('catalog.show', result.slug)"
                                class="flex items-center gap-3 px-3 py-2 text-sm text-[#C9BEA8] hover:text-brass"
                                @click="closeSearch"
                            >
                                <span class="line-clamp-1 flex-1">{{ result.title }}</span>
                            </Link>
                        </div>
                    </div>

                    <Link
                        v-if="auth.user"
                        :href="route('account.orders')"
                        class="hidden text-[#C9BEA8] transition-colors hover:text-brass sm:inline"
                    >
                        Account
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="hidden text-[#C9BEA8] transition-colors hover:text-brass sm:inline"
                    >
                        Account
                    </Link>

                    <Link :href="route('cart.index')" class="flex items-center gap-1.5 text-[#C9BEA8] transition-colors hover:text-brass">
                        Cart
                        <span
                            v-if="cartCount > 0"
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-brass font-mono text-[10px] text-espresso"
                        >
                            {{ cartCount > 99 ? '99+' : cartCount }}
                        </span>
                    </Link>
                </div>
            </div>

            <nav v-if="mobileMenuOpen" class="border-t border-[#4A3A2C] px-6 py-4 md:hidden">
                <div class="flex flex-col gap-3">
                    <Link
                        v-for="category in navCategories"
                        :key="category.id"
                        :href="route('catalog.index', { category: category.slug })"
                        class="text-sm text-[#C9BEA8] hover:text-brass"
                        @click="mobileMenuOpen = false"
                    >
                        {{ category.name }}
                    </Link>
                    <Link
                        :href="route('catalog.index')"
                        class="text-sm text-[#C9BEA8] hover:text-brass"
                        @click="mobileMenuOpen = false"
                    >
                        All products
                    </Link>
                    <template v-if="auth.user">
                        <Link
                            :href="route('account.orders')"
                            class="text-sm text-[#C9BEA8] hover:text-brass"
                            @click="mobileMenuOpen = false"
                        >
                            My orders
                        </Link>
                        <Link
                            v-if="auth.isAdmin"
                            :href="route('admin.dashboard')"
                            class="text-sm text-[#C9BEA8] hover:text-brass"
                            @click="mobileMenuOpen = false"
                        >
                            Admin
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm text-[#C9BEA8] hover:text-brass"
                            @click="mobileMenuOpen = false"
                        >
                            Log in
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <StitchDivider />

        <div v-if="flash?.success" class="border-b border-brass/30 bg-espresso px-4 py-2 text-center text-sm text-canvas">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="border-b border-oxblood/40 bg-oxblood/20 px-4 py-2 text-center text-sm text-oxblood">
            {{ flash.error }}
        </div>

        <main :class="props.fullBleed ? 'flex-1' : 'mx-auto w-full max-w-[1240px] flex-1 px-6 py-8 md:px-10'">
            <slot />
        </main>

        <StitchDivider />

        <footer class="bg-espresso px-6 pb-8 pt-14 text-[#C9BEA8] md:px-10">
            <div class="mx-auto max-w-[1240px]">
                <div class="grid grid-cols-2 gap-10 border-b border-[#4A3A2C] pb-11 md:grid-cols-4">
                    <div class="col-span-2 md:col-span-1">
                        <Link :href="route('home')" class="inline-flex items-center font-display text-[22px] font-[450] tracking-[0.5px] text-canvas">
                            <img v-if="footerLogoUrl" :src="footerLogoUrl" :alt="storeName" class="h-8 w-auto object-contain" />
                            <span v-else>{{ storeName }}</span>
                        </Link>
                        <p v-if="footerBlurb" class="mt-3.5 max-w-[260px] text-[13px] leading-[22px] text-[#8F8071]">
                            {{ footerBlurb }}
                        </p>
                        <p v-if="careEmail" class="mt-3 text-[13px] text-[#8F8071]">
                            <a :href="`mailto:${careEmail}`" class="hover:text-brass">{{ careEmail }}</a>
                        </p>
                        <div v-if="socialLinks.length" class="mt-4 flex flex-wrap gap-4">
                            <a
                                v-for="social in socialLinks"
                                :key="social.label"
                                :href="social.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[12px] uppercase tracking-[1px] text-[#8F8071] hover:text-brass"
                            >
                                {{ social.label }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <h5 class="mb-4 font-mono text-[11px] uppercase tracking-[2px] text-brass">Shop</h5>
                        <Link
                            v-for="category in navCategories.slice(0, 4)"
                            :key="category.id"
                            :href="route('catalog.index', { category: category.slug })"
                            class="mb-2.5 block text-sm text-[#C9BEA8] hover:text-brass"
                        >
                            {{ category.name }}
                        </Link>
                        <Link :href="route('catalog.index')" class="mb-2.5 block text-sm text-[#C9BEA8] hover:text-brass">
                            All products
                        </Link>
                    </div>
                    <div>
                        <h5 class="mb-4 font-mono text-[11px] uppercase tracking-[2px] text-brass">Care &amp; Returns</h5>
                        <Link
                            v-for="link in careLinks"
                            :key="`${link.type}-${link.value}`"
                            :href="resolveFooterLinkHref(link)"
                            class="mb-2.5 block text-sm text-[#C9BEA8] hover:text-brass"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                    <div>
                        <h5 class="mb-4 font-mono text-[11px] uppercase tracking-[2px] text-brass">About</h5>
                        <Link
                            v-for="link in aboutLinks"
                            :key="`${link.type}-${link.value}`"
                            :href="resolveFooterLinkHref(link)"
                            class="mb-2.5 block text-sm text-[#C9BEA8] hover:text-brass"
                        >
                            {{ link.label }}
                        </Link>
                        <Link
                            v-if="auth.isAdmin"
                            :href="route('admin.dashboard')"
                            class="mb-2.5 block text-sm text-[#C9BEA8] hover:text-brass"
                        >
                            Admin
                        </Link>
                    </div>
                </div>
                <div
                    class="flex flex-col gap-3 pt-7 font-mono text-[11px] tracking-[1px] text-[#8F8071] sm:flex-row sm:items-center sm:justify-between"
                >
                    <span>&copy; {{ new Date().getFullYear() }} {{ storeName }}<template v-if="location"> — {{ location }}</template></span>
                    <span v-if="copyrightTagline">{{ copyrightTagline }}</span>
                </div>
            </div>
        </footer>
    </div>
</template>
