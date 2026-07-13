<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { storageUrl } from '@/lib/storage';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface Variant {
    id: number;
    sku: string;
    price_cents: number;
    stock_quantity: number;
    is_default: boolean;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    status: string;
    brand?: string | null;
    variants: Variant[];
    images: { path: string; alt?: string | null }[];
    categories: { id: number; name: string }[];
}

interface Category {
    id: number;
    name: string;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    product: Product;
    categories: Category[];
    statuses: StatusOption[];
}>();

const { formatMoney } = useFormatMoney();
const defaultVariant = props.product.variants.find((v) => v.is_default) ?? props.product.variants[0];

const form = useForm({
    title: props.product.title,
    slug: props.product.slug,
    description: props.product.description ?? '',
    status: props.product.status,
    brand: props.product.brand ?? '',
    category_ids: props.product.categories.map((c) => c.id),
    image: null as File | null,
    default_variant: {
        id: defaultVariant?.id,
        sku: defaultVariant?.sku ?? '',
        price: defaultVariant ? defaultVariant.price_cents / 100 : 0,
        stock: defaultVariant?.stock_quantity ?? 0,
    },
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.products.update', props.product.id), {
        forceFormData: true,
    });
}

function onImageChange(event: Event) {
    form.image = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function toggleCategory(id: number) {
    const index = form.category_ids.indexOf(id);
    if (index >= 0) form.category_ids.splice(index, 1);
    else form.category_ids.push(id);
}

function destroyProduct() {
    if (confirm('Delete this product?')) {
        router.delete(route('admin.products.destroy', props.product.id));
    }
}
</script>

<template>
    <Head :title="`Edit ${product.title}`" />

    <AdminLayout>
        <div class="flex items-center justify-between">
            <div>
                <Link :href="route('admin.products.index')" class="text-sm text-teal-800 hover:underline">&larr; Products</Link>
                <h1 class="mt-2 text-2xl font-semibold">Edit product</h1>
            </div>
            <Link :href="route('admin.products.show', product.id)" class="text-sm text-teal-800 hover:underline">View</Link>
        </div>

        <form class="mt-6 max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input id="title" v-model="form.title" required />
                <InputError :message="form.errors.title" />
            </div>

            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
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

            <div v-if="product.images.length" class="flex gap-2">
                <img
                    v-for="(img, i) in product.images"
                    :key="i"
                    :src="storageUrl(img.path)"
                    :alt="img.alt ?? product.title"
                    class="h-16 w-16 rounded-md object-cover"
                />
            </div>

            <div class="space-y-2">
                <Label for="image">Replace image</Label>
                <Input id="image" type="file" accept="image/*" @change="onImageChange" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save changes</Button>
                <Button type="button" variant="destructive" @click="destroyProduct">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
