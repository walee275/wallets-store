<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormatMoney } from '@/composables/useFormatMoney';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

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
    images: { id: number; path: string; alt?: string | null; is_primary: boolean }[];
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
    images: [] as File[],
    removed_image_ids: [] as number[],
    primary_image_id: props.product.images.find((image) => image.is_primary)?.id ?? null,
    primary_image_upload_index: null as number | null,
    default_variant: {
        id: defaultVariant?.id,
        sku: defaultVariant?.sku ?? '',
        price: defaultVariant ? defaultVariant.price_cents / 100 : 0,
        stock: defaultVariant?.stock_quantity ?? 0,
    },
});

const imagePreviews = ref<{ url: string; name: string }[]>([]);
const imageInput = ref<HTMLInputElement | null>(null);

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.products.update', props.product.id), {
        forceFormData: true,
        onSuccess: () => {
            clearSelectedImages();
            form.removed_image_ids = [];
            form.primary_image_id = props.product.images.find((image) => image.is_primary)?.id ?? null;
        },
    });
}

function onImagesChange(event: Event) {
    const files = Array.from((event.target as HTMLInputElement).files ?? []);
    form.images = files;
    form.primary_image_upload_index = null;

    imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    imagePreviews.value = files.map((file) => ({ url: URL.createObjectURL(file), name: file.name }));
}

function removeSelectedImage(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index]!.url);
    imagePreviews.value.splice(index, 1);
    form.images.splice(index, 1);

    if (form.primary_image_upload_index === index) {
        form.primary_image_upload_index = null;
    } else if (form.primary_image_upload_index !== null && form.primary_image_upload_index > index) {
        form.primary_image_upload_index--;
    }

    if (form.images.length === 0 && imageInput.value) {
        imageInput.value.value = '';
    }
}

function clearSelectedImages() {
    imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    imagePreviews.value = [];
    form.images = [];
    form.primary_image_upload_index = null;

    if (imageInput.value) {
        imageInput.value.value = '';
    }
}

function toggleExistingImageRemoval(id: number) {
    const index = form.removed_image_ids.indexOf(id);

    if (index === -1) {
        form.removed_image_ids.push(id);

        if (form.primary_image_id === id) {
            form.primary_image_id = null;
        }
    } else {
        form.removed_image_ids.splice(index, 1);
    }
}

function selectExistingPrimary(id: number) {
    form.primary_image_id = id;
    form.primary_image_upload_index = null;
}

function selectUploadedPrimary(index: number) {
    form.primary_image_id = null;
    form.primary_image_upload_index = index;
}

onBeforeUnmount(() => {
    imagePreviews.value.forEach((preview) => URL.revokeObjectURL(preview.url));
});

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
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
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
                    <label
                        v-for="cat in categories"
                        :key="cat.id"
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-stone-200 px-3 py-1.5 text-sm"
                    >
                        <input type="checkbox" :checked="form.category_ids.includes(cat.id)" @change="toggleCategory(cat.id)" />
                        {{ cat.name }}
                    </label>
                </div>
            </div>

            <div v-if="product.images.length" class="space-y-2">
                <Label>Current images</Label>
                <p class="text-xs text-stone-500">Choose the display image used on product cards and the product detail page.</p>
                <div class="flex flex-wrap gap-3">
                    <div v-for="img in product.images" :key="img.id" class="relative">
                        <img
                            :src="storageUrl(img.path)"
                            :alt="img.alt ?? product.title"
                            class="h-24 w-24 rounded-md border border-stone-200 object-cover"
                            :class="{ 'opacity-30 grayscale': form.removed_image_ids.includes(img.id) }"
                        />
                        <span
                            v-if="form.primary_image_id === img.id && !form.removed_image_ids.includes(img.id)"
                            class="absolute left-1 top-1 rounded bg-stone-900/80 px-1.5 py-0.5 text-[10px] text-white"
                        >
                            Display image
                        </span>
                        <label
                            class="mt-1 flex cursor-pointer items-center gap-1 text-xs text-stone-600"
                            :class="{ 'pointer-events-none opacity-40': form.removed_image_ids.includes(img.id) }"
                        >
                            <input
                                type="radio"
                                name="primary-image"
                                :checked="form.primary_image_id === img.id"
                                :disabled="form.removed_image_ids.includes(img.id)"
                                @change="selectExistingPrimary(img.id)"
                            />
                            Use as display
                        </label>
                        <button
                            type="button"
                            class="absolute -right-2 -top-2 rounded-full px-2 py-1 text-xs text-white"
                            :class="form.removed_image_ids.includes(img.id) ? 'bg-teal-700 hover:bg-teal-800' : 'bg-stone-800 hover:bg-red-600'"
                            :aria-label="
                                form.removed_image_ids.includes(img.id)
                                    ? `Undo removal of ${img.alt ?? product.title}`
                                    : `Remove ${img.alt ?? product.title}`
                            "
                            @click="toggleExistingImageRemoval(img.id)"
                        >
                            {{ form.removed_image_ids.includes(img.id) ? 'Undo' : '×' }}
                        </button>
                    </div>
                </div>
                <InputError :message="form.errors.removed_image_ids" />
            </div>

            <div class="space-y-2">
                <Label for="images">Add images</Label>
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
                        <span
                            v-if="form.primary_image_upload_index === index"
                            class="absolute left-1 top-1 rounded bg-stone-900/80 px-1.5 py-0.5 text-[10px] text-white"
                        >
                            Display image
                        </span>
                        <button
                            type="button"
                            class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-stone-800 text-xs text-white hover:bg-red-600"
                            :aria-label="`Remove ${preview.name}`"
                            @click="removeSelectedImage(index)"
                        >
                            ×
                        </button>
                        <label class="mt-1 flex cursor-pointer items-center gap-1 text-xs text-stone-600">
                            <input
                                type="radio"
                                name="primary-image"
                                :checked="form.primary_image_upload_index === index"
                                @change="selectUploadedPrimary(index)"
                            />
                            Use as display
                        </label>
                        <p class="mt-1 w-24 truncate text-xs text-stone-500">{{ preview.name }}</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save changes</Button>
                <Button type="button" variant="destructive" @click="destroyProduct">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
