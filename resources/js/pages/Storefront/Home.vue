<script setup lang="ts">
import ProductCard from '@/components/storefront/ProductCard.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Link, Head } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
}

interface Collection {
    id: number;
    name: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    defaultVariant?: { price_cents: number } | null;
    primaryImage?: { path: string; alt?: string | null } | null;
}

defineProps<{
    featuredCollection: Collection | null;
    featuredProducts: Product[];
    categories: Category[];
    homepageBanner: { text?: string; url?: string } | null;
}>();
</script>

<template>
    <Head title="Home" />

    <StorefrontLayout>
        <section v-if="homepageBanner?.text" class="mb-8 overflow-hidden rounded-xl bg-teal-800 px-6 py-10 text-white">
            <div class="max-w-xl">
                <p class="text-lg font-medium sm:text-xl">{{ homepageBanner.text }}</p>
                <Link
                    v-if="homepageBanner.url"
                    :href="homepageBanner.url.startsWith('/') ? homepageBanner.url : homepageBanner.url"
                    class="mt-4 inline-block rounded-md bg-white px-4 py-2 text-sm font-medium text-teal-900 transition hover:bg-stone-100"
                >
                    Shop now
                </Link>
            </div>
        </section>

        <section v-if="categories.length" class="mb-10">
            <h2 class="mb-4 text-lg font-semibold">Shop by category</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                <Link
                    v-for="category in categories"
                    :key="category.id"
                    :href="route('catalog.index', { category: category.slug })"
                    class="rounded-lg border border-stone-200 bg-white px-4 py-5 text-center text-sm font-medium transition hover:border-teal-700/40 hover:shadow-sm"
                >
                    {{ category.name }}
                </Link>
            </div>
        </section>

        <section v-if="featuredProducts.length">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">
                    {{ featuredCollection?.name ?? 'Featured products' }}
                </h2>
                <Link :href="route('catalog.index')" class="text-sm text-teal-800 hover:underline">View all</Link>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
            </div>
        </section>

        <section v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
            <p class="text-stone-500">No featured products yet.</p>
            <Link :href="route('catalog.index')" class="mt-3 inline-block text-sm text-teal-800 hover:underline">Browse catalog</Link>
        </section>
    </StorefrontLayout>
</template>
