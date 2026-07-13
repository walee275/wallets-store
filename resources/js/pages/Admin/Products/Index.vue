<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Product {
    id: number;
    title: string;
    slug: string;
    status: string;
    variants?: { price_cents: number }[];
    variants_count?: number;
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
    filters: { search: string };
}>();

const { formatMoney } = useFormatMoney();
const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(route('admin.products.index'), { search: search.value || undefined }, { preserveState: true });
}
</script>

<template>
    <Head title="Products" />

    <AdminLayout>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-2xl font-semibold">Products</h1>
            <div class="flex flex-wrap gap-2">
                <Button as-child variant="outline" size="sm">
                    <a :href="route('admin.products.export')">Export CSV</a>
                </Button>
                <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                    <Link :href="route('admin.products.create')">Add product</Link>
                </Button>
            </div>
        </div>

        <form class="mb-6 flex gap-2" @submit.prevent="applySearch">
            <Input v-model="search" placeholder="Search products..." class="max-w-sm" />
            <Button type="submit" variant="outline">Search</Button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                        <th class="px-4 py-3 font-medium">Variants</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products.data" :key="product.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ product.title }}</td>
                        <td class="px-4 py-3 capitalize">{{ product.status }}</td>
                        <td class="px-4 py-3">
                            {{ product.variants?.[0] ? formatMoney(product.variants[0].price_cents) : '—' }}
                        </td>
                        <td class="px-4 py-3">{{ product.variants_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.products.edit', product.id)" class="text-teal-800 hover:underline">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <PaginationLinks :paginator="products" />
        </div>
    </AdminLayout>
</template>
