<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    paginator: Paginator;
}>();
</script>

<template>
    <div v-if="paginator.links.length > 3" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p v-if="paginator.from" class="text-sm text-stone-600">
            Showing {{ paginator.from }}–{{ paginator.to }} of {{ paginator.total }}
        </p>
        <nav class="flex flex-wrap justify-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md px-3 py-1.5 text-sm transition"
                    :class="link.active ? 'bg-teal-800 text-white' : 'text-stone-600 hover:bg-stone-100'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded-md px-3 py-1.5 text-sm text-stone-300"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
