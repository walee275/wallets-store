<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    slug: string;
    position: number;
    is_active: boolean;
    parent?: { name: string } | null;
}

interface Paginator {
    data: Category[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    categories: Paginator;
}>();

function destroyCategory(id: number) {
    if (confirm('Delete this category?')) {
        router.delete(route('admin.categories.destroy', id));
    }
}
</script>

<template>
    <Head title="Categories" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Categories</h1>
            <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                <Link :href="route('admin.categories.create')">Add category</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">Parent</th>
                        <th class="px-4 py-3 font-medium">Active</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="category in categories.data" :key="category.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ category.name }}</td>
                        <td class="px-4 py-3">{{ category.slug }}</td>
                        <td class="px-4 py-3">{{ category.parent?.name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ category.is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.categories.edit', category.id)" class="mr-3 text-teal-800 hover:underline">Edit</Link>
                            <button type="button" class="text-red-600 hover:underline" @click="destroyCategory(category.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="categories" /></div>
    </AdminLayout>
</template>
