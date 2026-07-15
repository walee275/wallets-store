<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

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
    images: [] as File[],
    default_variant: {
        sku: '',
        price: 0,
        stock: 0,
    },
});

const imagePreviews = ref<{ url: string; name: string }[]>([]);
const imageInput = ref<HTMLInputElement | null>(null);

function submit() {
    form.post(route('admin.products.store'), { forceFormData: true });
}

function onImagesChange(event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    form.images = files;

    imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    imagePreviews.value = files.map((file) => ({ url: URL.createObjectURL(file), name: file.name }));
}

function removeSelectedImage(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index]!.url);
    imagePreviews.value.splice(index, 1);
    form.images.splice(index, 1);

    if (form.images.length === 0 && imageInput.value) {
        imageInput.value.value = '';
    }
}

onBeforeUnmount(() => {
    imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
});

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
                <Label for="images">Product images</Label>
                <input
                    id="images"
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium"
                    @change="onImagesChange"
                />
                <InputError :message="form.errors.images" />
                <InputError v-for="(message, key) in form.errors" v-show="String(key).startsWith('images.')" :key="key" :message="message" />
            </div>

            <div v-if="imagePreviews.length" class="space-y-2">
                <Label>Selected images ({{ imagePreviews.length }})</Label>
                <div class="flex flex-wrap gap-3">
                    <div v-for="(preview, index) in imagePreviews" :key="preview.url" class="group relative">
                        <img :src="preview.url" :alt="preview.name" class="h-24 w-24 rounded-md border border-stone-200 object-cover" />
                        <button
                            type="button"
                            class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-stone-800 text-xs text-white hover:bg-red-600"
                            :aria-label="`Remove ${preview.name}`"
                            @click="removeSelectedImage(index)"
                        >
                            ×
                        </button>
                        <p class="mt-1 w-24 truncate text-xs text-stone-500">{{ preview.name }}</p>
                    </div>
                </div>
            </div>

            <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Create product</Button>
        </form>
    </AdminLayout>
</template>
