<script setup lang="ts">
import { storageUrl } from '@/lib/storage';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CategoryChild {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    image_path?: string | null;
    children?: CategoryChild[];
}

const props = defineProps<{
    category: Category;
    variant: 'cognac' | 'espresso' | 'oxblood';
}>();

const subtitle = computed(() => {
    const names = (props.category.children ?? []).map((child) => child.name).filter(Boolean);
    return names.length ? names.slice(0, 3).join(' · ') : null;
});

const imageUrl = computed(() => {
    const path = props.category.image_path;
    if (!path) {
        return null;
    }

    return storageUrl(path) || null;
});

const tileClass = computed(() => {
    if (imageUrl.value) {
        return 'bg-espresso';
    }

    switch (props.variant) {
        case 'cognac':
            return 'bg-[repeating-radial-gradient(circle_at_40%_40%,rgba(0,0,0,0.08)_0,rgba(0,0,0,0.08)_1px,transparent_2px,transparent_5px),linear-gradient(135deg,#8C5A2B,#5C3B1C)]';
        case 'espresso':
            return 'bg-[repeating-radial-gradient(circle_at_60%_30%,rgba(0,0,0,0.08)_0,rgba(0,0,0,0.08)_1px,transparent_2px,transparent_5px),linear-gradient(135deg,#2A1D16,#1C120C)]';
        case 'oxblood':
            return 'bg-[repeating-radial-gradient(circle_at_50%_60%,rgba(0,0,0,0.08)_0,rgba(0,0,0,0.08)_1px,transparent_2px,transparent_5px),linear-gradient(135deg,#5C1F22,#3B1416)]';
        default:
            return '';
    }
});
</script>

<template>
    <Link
        :href="route('catalog.index', { category: category.slug })"
        class="relative flex h-[300px] items-end overflow-hidden p-[26px]"
        :class="tileClass"
    >
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="category.name"
            class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
        />
        <span class="absolute inset-0 z-[1] bg-[linear-gradient(0deg,rgba(20,13,9,0.75)_0%,rgba(20,13,9,0.15)_60%)]" />
        <span class="relative z-[2]">
            <span class="font-display text-[22px] text-canvas">{{ category.name }}</span>
            <small v-if="subtitle" class="mt-1.5 block font-mono text-[11px] uppercase tracking-[1.5px] text-brass">
                {{ subtitle }}
            </small>
        </span>
    </Link>
</template>
