<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Review {
    id: number;
    rating: number;
    title?: string | null;
    body?: string | null;
    created_at: string;
    product?: { id: number; title: string; slug: string };
    user?: { name: string; email: string };
}

interface Paginator {
    data: Review[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    reviews: Paginator;
}>();

function approve(id: number) {
    router.patch(route('admin.reviews.approve', id), {}, { preserveScroll: true });
}

function reject(id: number) {
    router.patch(route('admin.reviews.reject', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Reviews" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Pending reviews</h1>

        <div v-if="reviews.data.length" class="space-y-4">
            <article v-for="review in reviews.data" :key="review.id" class="rounded-lg border border-stone-200 bg-white p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-amber-600">{{ '★'.repeat(review.rating) }}</span>
                            <span v-if="review.title" class="font-medium">{{ review.title }}</span>
                        </div>
                        <p v-if="review.body" class="mt-2 text-sm text-stone-600">{{ review.body }}</p>
                        <p class="mt-2 text-xs text-stone-500">
                            {{ review.user?.name ?? 'Anonymous' }} ·
                            <Link v-if="review.product" :href="route('catalog.show', review.product.slug)" class="text-teal-800 hover:underline">
                                {{ review.product.title }}
                            </Link>
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <Button size="sm" class="bg-teal-800 hover:bg-teal-900" @click="approve(review.id)">Approve</Button>
                        <Button size="sm" variant="outline" @click="reject(review.id)">Reject</Button>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500">
            No pending reviews.
        </div>

        <div class="mt-6"><PaginationLinks :paginator="reviews" /></div>
    </AdminLayout>
</template>
