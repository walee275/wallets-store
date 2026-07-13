<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
}

interface StatusOption {
    value: string;
    label: string;
}

defineProps<{
    categories: Category[];
    statuses: StatusOption[];
}>();

const form = useForm({
    title: '',
    slug: '',
    description: '',
    status: 'draft',
    brand: '',
    category_ids: [] as number[],
    image: null as File | null,
    default_variant: {
        sku: '',
        price: 0,
        stock: 0,
    },
});

function submit() {
    form.post(route('admin.products.store'), { forceFormData: true });
}

function onImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    form.image = file ?? null;
}

function toggleCategory(id: number) {
    const index = form.category_ids.indexOf(id);
    if (index >= 0) {
        form.category_ids.splice(index, 1);
    } else {
        form.category_ids.push(id);
    }
}
</script>

<template>
    <Head title="Create Product" />

    <AdminLayout>
        <Link :href="route('admin.products.index')" class="text-sm text-teal-800 hover:underline">&larr; Products</Link>
        <h1 class="mt-2 text-2xl font-semibold">Create product</h1>

        <form class="mt-6 max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input id="title" v-model="form.title" required />
                <InputError :message="form.errors.title" />
            </div>

            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
                <InputError :message="form.errors.slug" />
            </div>

            <div class="space-y-2">
                <Label for="description">Description</Label>
                <textarea id="description" v-model="form.description" rows="4" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
            </div>

            <div class="space-y-2">
                <Label for="status">Status</Label>
                <select id="status" v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="price">Price</Label>
                    <Input id="price" v-model.number="form.default_variant.price" type="number" min="0" step="0.01" required />
                    <InputError :message="form.errors['default_variant.price']" />
                </div>
                <div class="space-y-2">
                    <Label for="stock">Stock</Label>
                    <Input id="stock" v-model.number="form.default_variant.stock" type="number" min="0" required />
                </div>
                <div class="space-y-2">
                    <Label for="sku">SKU</Label>
                    <Input id="sku" v-model="form.default_variant.sku" />
                </div>
            </div>

            <div class="space-y-2">
                <Label>Categories</Label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="cat in categories" :key="cat.id" class="flex cursor-pointer items-center gap-2 rounded-md border border-stone-200 px-3 py-1.5 text-sm">
                        <input type="checkbox" :checked="form.category_ids.includes(cat.id)" @change="toggleCategory(cat.id)" />
                        {{ cat.name }}
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="image">Product image</Label>
                <Input id="image" type="file" accept="image/*" @change="onImageChange" />
            </div>

            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create product</Button>
        </form>
    </AdminLayout>
</template>
