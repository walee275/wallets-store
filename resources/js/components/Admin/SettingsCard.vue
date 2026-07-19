<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section class="mb-5 rounded-[10px] border border-stone-200 bg-white px-7 py-6">
        <header v-if="title || description" class="mb-5">
            <h3 v-if="title" class="text-[14.5px] font-semibold text-stone-900">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-[12.5px] leading-4 text-stone-500">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
