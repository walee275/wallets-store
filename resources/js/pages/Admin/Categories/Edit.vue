<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    image_path?: string | null;
    parent_id?: number | null;
    position: number;
    is_active: boolean;
}

interface Parent {
    id: number;
    name: string;
}

const props = defineProps<{
    category: Category;
    parents: Parent[];
}>();

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    parent_id: props.category.parent_id ?? null,
    position: props.category.position,
    is_active: props.category.is_active,
    image: null as File | null,
    remove_image: false,
});

const imagePreview = ref<string | null>(null);
const imageInput = ref<HTMLInputElement | null>(null);

const currentImageUrl = computed(() => {
    if (form.remove_image || !props.category.image_path) {
        return null;
    }

    return storageUrl(props.category.image_path);
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.categories.update', props.category.id), {
        forceFormData: true,
        onSuccess: () => clearSelectedImage(),
    });
}

function onImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.image = file;
    form.remove_image = false;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }

    if (file) {
        imagePreview.value = URL.createObjectURL(file);
    }
}

function clearSelectedImage() {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }

    form.image = null;

    if (imageInput.value) {
        imageInput.value.value = '';
    }
}

function markCurrentImageForRemoval() {
    form.remove_image = true;
    clearSelectedImage();
}

function undoImageRemoval() {
    form.remove_image = false;
}

function destroyCategory() {
    if (confirm('Delete this category?')) {
        router.delete(route('admin.categories.destroy', props.category.id));
    }
}

onBeforeUnmount(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <AdminLayout>
        <Link :href="route('admin.categories.index')" class="text-sm text-teal-800 hover:underline">&larr; Categories</Link>
        <h1 class="mt-2 text-2xl font-semibold">Edit category</h1>

        <form class="mt-6 max-w-md space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
                <InputError :message="form.errors.slug" />
            </div>
            <div class="space-y-2">
                <Label for="parent_id">Parent</Label>
                <select id="parent_id" v-model="form.parent_id" class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    <option :value="null">None</option>
                    <option v-for="parent in parents" :key="parent.id" :value="parent.id">{{ parent.name }}</option>
                </select>
            </div>
            <div class="space-y-2">
                <Label for="position">Position</Label>
                <Input id="position" v-model.number="form.position" type="number" min="0" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_active" v-model:checked="form.is_active" />
                <Label for="is_active">Active</Label>
            </div>

            <div class="space-y-2">
                <Label>Current image</Label>
                <div v-if="currentImageUrl" class="relative inline-block">
                    <img :src="currentImageUrl" :alt="category.name" class="h-24 w-24 rounded-md border border-stone-200 object-cover" />
                    <button
                        type="button"
                        class="absolute -right-2 -top-2 rounded-full bg-stone-800 px-2 py-1 text-xs text-white hover:bg-red-600"
                        aria-label="Remove current image"
                        @click="markCurrentImageForRemoval"
                    >
                        ×
                    </button>
                </div>
                <div v-else-if="category.image_path && form.remove_image" class="space-y-2">
                    <p class="text-xs text-stone-500">Current image will be removed when you save.</p>
                    <Button type="button" variant="outline" size="sm" @click="undoImageRemoval">Undo</Button>
                </div>
                <p v-else class="text-xs text-stone-500">No image uploaded.</p>
            </div>

            <div class="space-y-2">
                <Label for="image">Replace image</Label>
                <input
                    id="image"
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium"
                    @change="onImageChange"
                />
                <InputError :message="form.errors.image" />
                <div v-if="imagePreview" class="relative inline-block">
                    <img :src="imagePreview" alt="Selected category image" class="h-24 w-24 rounded-md border border-stone-200 object-cover" />
                    <button
                        type="button"
                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-stone-800 text-xs text-white hover:bg-red-600"
                        aria-label="Remove selected image"
                        @click="clearSelectedImage"
                    >
                        ×
                    </button>
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="destructive" @click="destroyCategory">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
