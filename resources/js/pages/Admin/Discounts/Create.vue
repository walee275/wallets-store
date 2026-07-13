<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface TypeOption {
    value: string;
    label: string;
}

defineProps<{
    types: TypeOption[];
}>();

const form = useForm({
    code: '',
    type: 'percent',
    value: 0,
    min_order_cents: null as number | null,
    max_uses: null as number | null,
    max_uses_per_user: null as number | null,
    starts_at: '',
    ends_at: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.discounts.store'));
}
</script>

<template>
    <Head title="Create Discount" />

    <AdminLayout>
        <Link :href="route('admin.discounts.index')" class="text-sm text-teal-800 hover:underline">&larr; Discounts</Link>
        <h1 class="mt-2 text-2xl font-semibold">Create discount</h1>

        <form class="mt-6 max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="code">Code</Label>
                <Input id="code" v-model="form.code" required />
                <InputError :message="form.errors.code" />
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
            <div class="space-y-2">
                <Label for="starts_at">Starts at</Label>
                <Input id="starts_at" v-model="form.starts_at" type="datetime-local" />
            </div>
            <div class="space-y-2">
                <Label for="ends_at">Ends at</Label>
                <Input id="ends_at" v-model="form.ends_at" type="datetime-local" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model:checked="form.is_active" />
                <Label for="is_active">Active</Label>
            </div>
            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create</Button>
        </form>
    </AdminLayout>
</template>
