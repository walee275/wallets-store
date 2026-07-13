<script setup lang="ts">
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, Link, router } from '@inertiajs/vue3';

interface WishlistItem {
    id: number;
    variant: {
        id: number;
        price_cents: number;
        product: {
            id: number;
            title: string;
            slug: string;
            images: { path: string; alt?: string | null }[];
        };
    };
}

interface Wishlist {
    id: number;
    items: WishlistItem[];
}

defineProps<{
    wishlist: Wishlist;
}>();

const { formatMoney } = useFormatMoney();

function addToCart(variantId: number) {
    router.post(route('cart.store'), { variant_id: variantId, quantity: 1 });
}
</script>

<template>
    <Head title="Wishlist" />

    <StorefrontLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Wishlist</h1>
            <nav class="flex gap-4 text-sm">
                <Link :href="route('account.orders')" class="text-teal-800 hover:underline">Orders</Link>
                <Link :href="route('account.addresses')" class="text-teal-800 hover:underline">Addresses</Link>
            </nav>
        </div>

        <div v-if="wishlist.items.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="item in wishlist.items"
                :key="item.id"
                class="flex flex-col overflow-hidden rounded-lg border border-stone-200 bg-white"
            >
                <Link :href="route('catalog.show', item.variant.product.slug)" class="aspect-square overflow-hidden bg-stone-100">
                    <img
                        v-if="item.variant.product.images[0]"
                        :src="storageUrl(item.variant.product.images[0].path)"
                        :alt="item.variant.product.images[0].alt ?? item.variant.product.title"
                        class="h-full w-full object-cover"
                    />
                </Link>
                <div class="flex flex-1 flex-col gap-2 p-4">
                    <Link :href="route('catalog.show', item.variant.product.slug)" class="font-medium hover:text-teal-800">
                        {{ item.variant.product.title }}
                    </Link>
                    <p class="text-sm font-semibold text-teal-800">{{ formatMoney(item.variant.price_cents) }}</p>
                    <button
                        type="button"
                        class="mt-auto rounded-md bg-teal-800 px-3 py-2 text-sm text-white hover:bg-teal-900"
                        @click="addToCart(item.variant.id)"
                    >
                        Add to cart
                    </button>
                </div>
            </article>
        </div>

        <div v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500">
            Your wishlist is empty.
            <Link :href="route('catalog.index')" class="mt-2 block text-teal-800 hover:underline">Browse products</Link>
        </div>
    </StorefrontLayout>
</template>
