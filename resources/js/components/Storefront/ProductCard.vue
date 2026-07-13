<script setup lang="ts">
import { useFormatMoney } from '@/composables/useFormatMoney';
import { storageUrl } from '@/lib/storage';
import { Link } from '@inertiajs/vue3';

interface ProductVariant {
    id: number;
    price_cents: number;
    compare_at_cents?: number | null;
}

interface ProductImage {
    path: string;
    alt?: string | null;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    defaultVariant?: ProductVariant | null;
    primaryImage?: ProductImage | null;
}

defineProps<{
    product: Product;
}>();

const { formatMoney } = useFormatMoney();
</script>

<template>
    <Link
        :href="route('catalog.show', product.slug)"
        class="group flex flex-col overflow-hidden rounded-lg border border-stone-200 bg-white transition hover:border-teal-700/40 hover:shadow-sm"
    >
        <div class="aspect-square overflow-hidden bg-stone-100">
            <img
                v-if="product.primaryImage?.path"
                :src="storageUrl(product.primaryImage.path)"
                :alt="product.primaryImage.alt ?? product.title"
                class="h-full w-full object-cover transition group-hover:scale-105"
            />
            <div v-else class="flex h-full items-center justify-center text-sm text-stone-400">No image</div>
        </div>
        <div class="flex flex-1 flex-col gap-1 p-3">
            <h3 class="line-clamp-2 text-sm font-medium text-[#1a1a1a]">{{ product.title }}</h3>
            <div v-if="product.defaultVariant" class="flex items-baseline gap-2">
                <span class="text-sm font-semibold text-teal-800">{{ formatMoney(product.defaultVariant.price_cents) }}</span>
                <span
                    v-if="product.defaultVariant.compare_at_cents && product.defaultVariant.compare_at_cents > product.defaultVariant.price_cents"
                    class="text-xs text-stone-400 line-through"
                >
                    {{ formatMoney(product.defaultVariant.compare_at_cents) }}
                </span>
            </div>
        </div>
    </Link>
</template>
