<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Customer {
    id: number;
    name: string;
    email: string;
    orders_count?: number;
    created_at: string;
}

interface Paginator {
    data: Customer[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    customers: Paginator;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? '');

function applySearch() {
    router.get(route('admin.customers.index'), { search: search.value || undefined }, { preserveState: true });
}
</script>

<template>
    <Head title="Customers" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Customers</h1>

        <form class="mb-6 flex gap-2" @submit.prevent="applySearch">
            <Input v-model="search" placeholder="Search by name or email..." class="max-w-sm" />
            <Button type="submit" variant="outline">Search</Button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Orders</th>
                        <th class="px-4 py-3 font-medium">Joined</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="customer in customers.data" :key="customer.id" class="border-b border-stone-100">
                        <td class="px-4 py-3 font-medium">{{ customer.name }}</td>
                        <td class="px-4 py-3">{{ customer.email }}</td>
                        <td class="px-4 py-3">{{ customer.orders_count ?? 0 }}</td>
                        <td class="px-4 py-3">{{ new Date(customer.created_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.customers.show', customer.id)" class="text-teal-800 hover:underline">View</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6"><PaginationLinks :paginator="customers" /></div>
    </AdminLayout>
</template>
