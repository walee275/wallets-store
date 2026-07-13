<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Page {
    id: number;
    title: string;
    slug: string;
    is_published: boolean;
    updated_at: string;
}

interface Paginator {
    data: Page[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    pages: Paginator;
}>();

function destroyPage(id: number) {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.pages.destroy', id));
    }
}
</script>

<template>
    <Head title="Pages" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Pages</h1>
            <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                <Link :href="route('admin.pages.create')">Add page</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium">Published</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="page in pages.data" :key="page.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ page.title }}</td>
                        <td class="px-4 py-3">{{ page.slug }}</td>
                        <td class="px-4 py-3">{{ page.is_published ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3">{{ new Date(page.updated_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.pages.edit', page.id)" class="mr-3 text-teal-800 hover:underline">Edit</Link>
                            <Link :href="route('pages.show', page.slug)" class="mr-3 text-stone-500 hover:underline" target="_blank">View</Link>
                            <button type="button" class="text-red-600 hover:underline" @click="destroyPage(page.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="pages" /></div>
    </AdminLayout>
</template>
