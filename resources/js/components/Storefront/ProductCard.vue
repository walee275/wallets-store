<script setup lang="ts">
import { useFormatMoney } from '@/composables/useFormatMoney';
import { storageUrl } from '@/lib/storage';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ProductVariant {
    id: number;
    price_cents: number;
    compare_at_cents?: number | null;
}

interface ProductImage {
    path: string;
    alt?: string | null;
}

interface ProductOption {
    type?: string;
    value?: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    brand?: string | null;
    defaultVariant?: ProductVariant | null;
    primaryImage?: ProductImage | null;
    /** Optional color/material hints from homepage mapping */
    leatherColor?: string | null;
    leatherMeta?: string | null;
    options?: ProductOption[] | null;
}

const props = defineProps<{
    product: Product;
}>();

const { formatMoney } = useFormatMoney();

type SwatchKey = 'cognac' | 'espresso' | 'oxblood' | 'brass';

const colorLabel = computed(() => {
    if (props.product.leatherColor) {
        return props.product.leatherColor;
    }

    const fromOptions = (props.product.options ?? []).find((option) => {
        const type = (option.type ?? '').toLowerCase();
        return type === 'color' || type === 'colour';
    })?.value;

    return fromOptions ?? null;
});

const swatchKey = computed<SwatchKey>(() => {
    const haystack = [colorLabel.value, props.product.title, props.product.brand]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    if (/(oxblood|burgundy|wine|crimson)/.test(haystack)) {
        return 'oxblood';
    }
    if (/(espresso|ebony|black|dark|charcoal|coffee)/.test(haystack)) {
        return 'espresso';
    }
    if (/(brass|natural|tan|honey|sand|camel)/.test(haystack)) {
        return 'brass';
    }
    if (/(cognac|chestnut|brown|rust|saddle)/.test(haystack)) {
        return 'cognac';
    }

    // Stable fallback by product id so grids look varied without images
    const cycle: SwatchKey[] = ['cognac', 'espresso', 'oxblood', 'brass'];
    return cycle[props.product.id % cycle.length]!;
});

const swatchClass = computed(() => `swatch swatch-${swatchKey.value}`);

const tagLabel = computed(() => {
    if (colorLabel.value) {
        return colorLabel.value;
    }

    const labels: Record<SwatchKey, string> = {
        cognac: 'Cognac',
        espresso: 'Espresso',
        oxblood: 'Oxblood',
        brass: 'Natural Tan',
    };

    return labels[swatchKey.value];
});

const meta = computed(() => props.product.leatherMeta ?? props.product.brand ?? null);

const imageUrl = computed(() => {
    const path = props.product.primaryImage?.path;
    if (!path) {
        return null;
    }
    const url = storageUrl(path);
    return url || null;
});
</script>

<template>
    <Link :href="route('catalog.show', product.slug)" class="group flex cursor-pointer flex-col">
        <div
            class="relative flex aspect-square items-center justify-center overflow-hidden border border-hairline transition-[border-color] duration-200 group-hover:border-cognac"
        >
            <img
                v-if="imageUrl"
                :src="imageUrl"
                :alt="product.primaryImage?.alt ?? product.title"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
            />
            <template v-else>
                <div :class="[swatchClass, 'group-hover:scale-[1.04]']" />
                <div
                    class="absolute left-3.5 top-3.5 z-[3] bg-[rgba(20,13,9,0.55)] px-2.5 py-1 font-mono text-[10px] uppercase tracking-[1px] text-[#F5EFE3]"
                >
                    {{ tagLabel }}
                </div>
            </template>
            <div
                v-if="imageUrl && tagLabel"
                class="absolute left-3.5 top-3.5 z-[3] bg-[rgba(20,13,9,0.55)] px-2.5 py-1 font-mono text-[10px] uppercase tracking-[1px] text-[#F5EFE3]"
            >
                {{ tagLabel }}
            </div>
        </div>
        <div class="pt-4">
            <h3 class="font-display text-[17px] font-[450] text-ink">{{ product.title }}</h3>
            <p v-if="meta" class="mt-1 font-mono text-[11px] tracking-[0.5px] text-atelier-stone">
                {{ meta }}
            </p>
            <p v-if="product.defaultVariant" class="mt-2.5 font-mono text-[13px] text-ink">
                {{ formatMoney(product.defaultVariant.price_cents) }}
            </p>
        </div>
    </Link>
</template>
