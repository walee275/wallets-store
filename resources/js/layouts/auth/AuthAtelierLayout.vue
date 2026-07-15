<script setup lang="ts">
import BrandPanel from '@/components/Auth/BrandPanel.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-screen grid-cols-1 bg-canvas font-sans text-ink min-[860px]:grid-cols-2">
        <BrandPanel />

        <div class="flex items-center justify-center bg-canvas p-10">
            <div class="w-full max-w-[380px]">
                <template v-if="title">
                    <p class="mb-3.5 font-mono text-[11px] uppercase tracking-[2.5px] text-atelier-stone">Account</p>
                    <h1 class="mb-2.5 font-display text-[34px] font-[450] text-ink">{{ title }}</h1>
                    <p v-if="description" class="mb-9 text-sm leading-[22px] text-atelier-stone">{{ description }}</p>
                </template>
                <slot />
            </div>
        </div>
    </div>
</template>
