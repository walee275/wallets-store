<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Collection {
    id: number;
    name: string;
    slug: string;
    type: string;
    is_featured: boolean;
    products_count?: number;
}

interface Paginator {
    data: Collection[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    collections: Paginator;
}>();

function destroyCollection(id: number) {
    if (confirm('Delete this collection?')) {
        router.delete(route('admin.collections.destroy', id));
    }
}
</script>

<template>
    <Head title="Collections" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Collections</h1>
            <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                <Link :href="route('admin.collections.create')">Add collection</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Products</th>
                        <th class="px-4 py-3 font-medium">Featured</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="collection in collections.data" :key="collection.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ collection.name }}</td>
                        <td class="px-4 py-3 capitalize">{{ collection.type }}</td>
                        <td class="px-4 py-3">{{ collection.products_count ?? 0 }}</td>
                        <td class="px-4 py-3">{{ collection.is_featured ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.collections.edit', collection.id)" class="mr-3 text-teal-800 hover:underline">Edit</Link>
                            <button type="button" class="text-red-600 hover:underline" @click="destroyCollection(collection.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="collections" /></div>
    </AdminLayout>
</template>
