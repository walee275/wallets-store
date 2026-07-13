<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Search, ShoppingCart, X } from 'lucide-vue-next';
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

const page = usePage();
const mobileMenuOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchOpen = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const storeName = computed(() => (page.props.store as { name?: string })?.name ?? page.props.name);
const navCategories = computed(() => (page.props.navCategories as NavCategory[]) ?? []);
const cartCount = computed(() => (page.props.cartCount as number) ?? 0);
const flash = computed(() => page.props.flash as { success?: string | null; error?: string | null });
const auth = computed(() => page.props.auth as { user?: { name: string } | null; isAdmin?: boolean });

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
    <div class="min-h-screen bg-[#faf8f5] text-[#1a1a1a]">
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-[#faf8f5]/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <button
                    type="button"
                    class="rounded-md p-2 text-stone-600 hover:bg-stone-100 md:hidden"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                    <X v-else class="h-5 w-5" />
                </button>

                <Link :href="route('home')" class="text-lg font-semibold tracking-tight text-[#1a1a1a]">
                    {{ storeName }}
                </Link>

                <nav class="ml-4 hidden items-center gap-4 md:flex">
                    <Link
                        v-for="category in navCategories"
                        :key="category.id"
                        :href="route('catalog.index', { category: category.slug })"
                        class="text-sm text-stone-600 transition hover:text-teal-800"
                    >
                        {{ category.name }}
                    </Link>
                </nav>

                <div class="relative ml-auto w-full max-w-xs">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search products..."
                        class="pl-9"
                        @input="onSearchInput"
                        @focus="searchQuery.length >= 2 && searchResults.length && (searchOpen = true)"
                        @blur="setTimeout(closeSearch, 150)"
                    />
                    <div
                        v-if="searchOpen"
                        class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-md border border-stone-200 bg-white shadow-lg"
                    >
                        <Link
                            v-for="result in searchResults"
                            :key="result.id"
                            :href="route('catalog.show', result.slug)"
                            class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-stone-50"
                            @click="closeSearch"
                        >
                            <span class="line-clamp-1 flex-1">{{ result.title }}</span>
                        </Link>
                    </div>
                </div>

                <Link :href="route('cart.index')" class="relative rounded-md p-2 text-stone-600 hover:bg-stone-100">
                    <ShoppingCart class="h-5 w-5" />
                    <span
                        v-if="cartCount > 0"
                        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-teal-800 px-1 text-[10px] font-medium text-white"
                    >
                        {{ cartCount > 99 ? '99+' : cartCount }}
                    </span>
                </Link>

                <div class="hidden items-center gap-2 sm:flex">
                    <template v-if="auth.user">
                        <Link :href="route('account.orders')" class="text-sm text-stone-600 hover:text-teal-800">Orders</Link>
                        <Link v-if="auth.isAdmin" :href="route('admin.dashboard')" class="text-sm text-stone-600 hover:text-teal-800">Admin</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm text-stone-600 hover:text-teal-800">Log in</Link>
                        <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                            <Link :href="route('register')">Sign up</Link>
                        </Button>
                    </template>
                </div>
            </div>

            <nav v-if="mobileMenuOpen" class="border-t border-stone-200 px-4 py-3 md:hidden">
                <div class="flex flex-col gap-2">
                    <Link
                        v-for="category in navCategories"
                        :key="category.id"
                        :href="route('catalog.index', { category: category.slug })"
                        class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100"
                        @click="mobileMenuOpen = false"
                    >
                        {{ category.name }}
                    </Link>
                    <Link :href="route('catalog.index')" class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100" @click="mobileMenuOpen = false">
                        All products
                    </Link>
                    <template v-if="auth.user">
                        <Link :href="route('account.orders')" class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100" @click="mobileMenuOpen = false">My orders</Link>
                        <Link :href="route('account.addresses')" class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100" @click="mobileMenuOpen = false">Addresses</Link>
                        <Link :href="route('account.wishlist')" class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100" @click="mobileMenuOpen = false">Wishlist</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md px-2 py-1.5 text-sm text-stone-600 hover:bg-stone-100" @click="mobileMenuOpen = false">Log in</Link>
                    </template>
                </div>
            </nav>
        </header>

        <div v-if="flash?.success" class="border-b border-teal-200 bg-teal-50 px-4 py-2 text-center text-sm text-teal-900">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="border-b border-red-200 bg-red-50 px-4 py-2 text-center text-sm text-red-900">
            {{ flash.error }}
        </div>

        <main class="mx-auto max-w-6xl px-4 py-6">
            <slot />
        </main>

        <footer class="mt-auto border-t border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-stone-500">&copy; {{ new Date().getFullYear() }} {{ storeName }}</p>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <Link :href="route('pages.show', 'about')" class="text-stone-600 hover:text-teal-800">About</Link>
                    <Link :href="route('pages.show', 'returns-policy')" class="text-stone-600 hover:text-teal-800">Returns</Link>
                    <Link :href="route('catalog.index')" class="text-stone-600 hover:text-teal-800">Shop</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
