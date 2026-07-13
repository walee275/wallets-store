<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface Discount {
    id: number;
    code: string;
    type: string;
    value: number;
    min_order_cents?: number | null;
    max_uses?: number | null;
    max_uses_per_user?: number | null;
    starts_at?: string | null;
    ends_at?: string | null;
    is_active: boolean;
}

interface TypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    discount: Discount;
    types: TypeOption[];
}>();

const form = useForm({
    code: props.discount.code,
    type: props.discount.type,
    value: props.discount.value,
    min_order_cents: props.discount.min_order_cents,
    max_uses: props.discount.max_uses,
    max_uses_per_user: props.discount.max_uses_per_user,
    starts_at: props.discount.starts_at ?? '',
    ends_at: props.discount.ends_at ?? '',
    is_active: props.discount.is_active,
});

function submit() {
    form.put(route('admin.discounts.update', props.discount.id));
}

function destroyDiscount() {
    if (confirm('Delete this discount?')) {
        router.delete(route('admin.discounts.destroy', props.discount.id));
    }
}
</script>

<template>
    <Head :title="`Edit ${discount.code}`" />

    <AdminLayout>
        <Link :href="route('admin.discounts.index')" class="text-sm text-teal-800 hover:underline">&larr; Discounts</Link>
        <h1 class="mt-2 text-2xl font-semibold">Edit discount</h1>

        <form class="mt-6 max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="code">Code</Label>
                <Input id="code" v-model="form.code" required />
            </div>
            <div class="space-y-2">
                <Label for="type">Type</Label>
                <select id="type" v-model="form.type" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label for="value">Value</Label>
                <Input id="value" v-model.number="form.value" type="number" min="0" required />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model:checked="form.is_active" />
                <Label for="is_active">Active</Label>
            </div>
            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="destructive" @click="destroyDiscount">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
