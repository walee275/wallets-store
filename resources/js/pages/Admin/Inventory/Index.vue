<script setup lang="ts">
import PaginationLinks from '@/components/storefront/PaginationLinks.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Variant {
    id: number;
    sku: string;
    stock_quantity: number;
    low_stock_threshold: number;
    product?: { id: number; title: string; slug: string };
}

interface Paginator {
    data: Variant[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

defineProps<{
    variants: Paginator;
}>();

const adjustingId = ref<number | null>(null);

const adjustForm = useForm({
    delta: 0,
    type: 'adjustment',
    note: '',
});

function startAdjust(variantId: number) {
    adjustingId.value = variantId;
    adjustForm.reset();
}

function submitAdjust(variantId: number) {
    adjustForm.post(route('admin.inventory.adjust', variantId), {
        preserveScroll: true,
        onSuccess: () => {
            adjustingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Inventory" />

    <AdminLayout>
        <h1 class="mb-6 text-2xl font-semibold">Low stock inventory</h1>

        <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-stone-200 bg-stone-50 text-stone-500">
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Stock</th>
                        <th class="px-4 py-3 font-medium">Threshold</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="variant in variants.data" :key="variant.id" class="border-b border-stone-100">
                        <td class="px-4 py-3">
                            <Link v-if="variant.product" :href="route('admin.products.edit', variant.product.id)" class="font-medium text-teal-800 hover:underline">
                                {{ variant.product.title }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ variant.sku }}</td>
                        <td class="px-4 py-3" :class="variant.stock_quantity <= variant.low_stock_threshold ? 'font-medium text-amber-700' : ''">
                            {{ variant.stock_quantity }}
                        </td>
                        <td class="px-4 py-3">{{ variant.low_stock_threshold }}</td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-sm text-teal-800 hover:underline" @click="startAdjust(variant.id)">
                                Adjust
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form
            v-if="adjustingId"
            class="mt-6 max-w-sm space-y-3 rounded-lg border border-stone-200 bg-white p-5"
            @submit.prevent="submitAdjust(adjustingId!)"
        >
            <h2 class="font-semibold">Adjust stock</h2>
            <div class="space-y-2">
                <Label for="delta">Delta (+/-)</Label>
                <Input id="delta" v-model.number="adjustForm.delta" type="number" required />
            </div>
            <div class="space-y-2">
                <Label for="note">Note</Label>
                <Input id="note" v-model="adjustForm.note" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="adjustForm.processing">Save</Button>
                <Button type="button" variant="outline" @click="adjustingId = null">Cancel</Button>
            </div>
        </form>

        <div class="mt-6"><PaginationLinks :paginator="variants" /></div>
    </AdminLayout>
</template>
