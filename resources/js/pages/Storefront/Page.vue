<script setup lang="ts">
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head } from '@inertiajs/vue3';

interface Page {
    id: number;
    title: string;
    slug: string;
    body?: string | null;
    seo_title?: string | null;
    seo_description?: string | null;
}

defineProps<{
    page: Page;
}>();
</script>

<template>
    <Head :title="page.seo_title ?? page.title">
        <meta v-if="page.seo_description" name="description" :content="page.seo_description" />
    </Head>

    <StorefrontLayout>
        <article class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-semibold">{{ page.title }}</h1>
            <div
                v-if="page.body"
                class="prose prose-stone mt-8 max-w-none"
                v-html="page.body"
            />
            <p v-else class="mt-8 text-stone-500">This page has no content yet.</p>
        </article>
    </StorefrontLayout>
</template>
