<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { storageUrl } from '@/lib/storage';
import { Head, Link } from '@inertiajs/vue3';

interface Variant {
    id: number;
    sku: string;
    price_cents: number;
    stock_quantity: number;
    is_default: boolean;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    status: string;
    brand?: string | null;
    variants: Variant[];
    images: { path: string; alt?: string | null }[];
    categories: { id: number; name: string }[];
}

defineProps<{
    product: Product;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Head :title="product.title" />

    <AdminLayout>
        <Link :href="route('admin.products.index')" class="text-sm text-teal-800 hover:underline">&larr; Products</Link>

        <div class="mt-4 flex flex-col gap-6 lg:flex-row">
            <div v-if="product.images.length" class="shrink-0">
                <img
                    :src="storageUrl(product.images[0].path)"
                    :alt="product.images[0].alt ?? product.title"
                    class="h-48 w-48 rounded-lg object-cover"
                />
            </div>
            <div class="flex-1">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">{{ product.title }}</h1>
                        <p class="text-sm text-stone-500">{{ product.slug }} · <span class="capitalize">{{ product.status }}</span></p>
                    </div>
                    <Link :href="route('admin.products.edit', product.id)" class="text-sm text-teal-800 hover:underline">Edit</Link>
                </div>

                <div v-if="product.description" class="prose prose-stone mt-4 max-w-none text-sm" v-html="product.description" />

                <div class="mt-6">
                    <h2 class="font-semibold">Variants</h2>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-200 text-stone-500">
                                <th class="py-2 text-left font-medium">SKU</th>
                                <th class="py-2 text-left font-medium">Price</th>
                                <th class="py-2 text-left font-medium">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="variant in product.variants" :key="variant.id" class="border-b border-stone-100">
                                <td class="py-2">{{ variant.sku }}{{ variant.is_default ? ' (default)' : '' }}</td>
                                <td class="py-2">{{ formatMoney(variant.price_cents) }}</td>
                                <td class="py-2">{{ variant.stock_quantity }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="product.categories.length" class="mt-4">
                    <h2 class="font-semibold">Categories</h2>
                    <p class="mt-1 text-sm text-stone-600">{{ product.categories.map((c) => c.name).join(', ') }}</p>
                </div>

                <Link :href="route('catalog.show', product.slug)" class="mt-4 inline-block text-sm text-teal-800 hover:underline">
                    View on storefront
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
