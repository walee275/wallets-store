<script setup lang="ts">
import ProductCard from '@/components/Storefront/ProductCard.vue';
import PaginationLinks from '@/components/Storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    default_variant?: { id: number; price_cents: number } | null;
    defaultVariant?: { id: number; price_cents: number } | null;
    primary_image?: { path: string } | null;
    primaryImage?: { path: string } | null;
}

interface Paginator {
    data: Product[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    products: Paginator;
    categories: Category[];
    filters: {
        category?: string;
        min_price?: number;
        max_price?: number;
        sort?: string;
        q?: string;
    };
}>();

const form = reactive({
    category: props.filters.category ?? '',
    min_price: props.filters.min_price?.toString() ?? '',
    max_price: props.filters.max_price?.toString() ?? '',
    sort: props.filters.sort ?? 'newest',
    q: props.filters.q ?? '',
});

function applyFilters() {
    router.get(
        route('catalog.index'),
        {
            category: form.category || undefined,
            min_price: form.min_price || undefined,
            max_price: form.max_price || undefined,
            sort: form.sort || undefined,
            q: form.q || undefined,
        },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Products" />

    <StorefrontLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Products</h1>
            <p class="mt-1 text-sm text-stone-600">{{ products.total }} products found</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-4 rounded-lg border border-stone-200 bg-white p-4">
                <form class="space-y-4" @submit.prevent="applyFilters">
                    <div class="space-y-2">
                        <Label for="q">Search</Label>
                        <Input id="q" v-model="form.q" placeholder="Search..." />
                    </div>

                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <select
                            id="category"
                            v-model="form.category"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.slug">{{ cat.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-2">
                            <Label for="min_price">Min price (Rs)</Label>
                            <Input id="min_price" v-model="form.min_price" type="number" min="0" step="0.01" />
                        </div>
                        <div class="space-y-2">
                            <Label for="max_price">Max price (Rs)</Label>
                            <Input id="max_price" v-model="form.max_price" type="number" min="0" step="0.01" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="sort">Sort</Label>
                        <select id="sort" v-model="form.sort" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="newest">Newest</option>
                            <option value="price_asc">Price: low to high</option>
                            <option value="price_desc">Price: high to low</option>
                        </select>
                    </div>

                    <Button type="submit" class="w-full bg-teal-800 hover:bg-teal-900">Apply filters</Button>
                </form>
            </aside>

            <div>
                <div v-if="products.data.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
                </div>
                <div v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500">
                    No products match your filters.
                </div>
                <div class="mt-8">
                    <PaginationLinks :paginator="products" />
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
