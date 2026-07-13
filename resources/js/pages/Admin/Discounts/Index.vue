<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Discount {
    id: number;
    code: string;
    type: string;
    value: number;
    is_active: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
}

interface Paginator {
    data: Discount[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    discounts: Paginator;
}>();

function destroyDiscount(id: number) {
    if (confirm('Delete this discount?')) {
        router.delete(route('admin.discounts.destroy', id));
    }
}
</script>

<template>
    <Head title="Discounts" />

    <AdminLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Discounts</h1>
            <Button as-child size="sm" class="bg-teal-800 hover:bg-teal-900">
                <Link :href="route('admin.discounts.create')">Add discount</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Value</th>
                        <th class="px-4 py-3 font-medium">Active</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="discount in discounts.data" :key="discount.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ discount.code }}</td>
                        <td class="px-4 py-3 capitalize">{{ discount.type.replace('_', ' ') }}</td>
                        <td class="px-4 py-3">{{ discount.value }}</td>
                        <td class="px-4 py-3">{{ discount.is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.discounts.edit', discount.id)" class="mr-3 text-teal-800 hover:underline">Edit</Link>
                            <button type="button" class="text-red-600 hover:underline" @click="destroyDiscount(discount.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="discounts" /></div>
    </AdminLayout>
</template>
