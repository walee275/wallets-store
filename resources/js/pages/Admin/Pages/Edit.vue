<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface Page {
    id: number;
    title: string;
    slug: string;
    body?: string | null;
    seo_title?: string | null;
    seo_description?: string | null;
    is_published: boolean;
}

const props = defineProps<{
    page: Page;
}>();

const form = useForm({
    title: props.page.title,
    slug: props.page.slug,
    body: props.page.body ?? '',
    seo_title: props.page.seo_title ?? '',
    seo_description: props.page.seo_description ?? '',
    is_published: props.page.is_published,
});

function submit() {
    form.put(route('admin.pages.update', props.page.id));
}

function destroyPage() {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.pages.destroy', props.page.id));
    }
}
</script>

<template>
    <Head :title="`Edit ${page.title}`" />

    <AdminLayout>
        <Link :href="route('admin.pages.index')" class="text-sm text-teal-800 hover:underline">&larr; Pages</Link>
        <h1 class="mt-2 text-2xl font-semibold">Edit page</h1>

        <form class="mt-6 max-w-2xl space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="title">Title</Label>
                <Input id="title" v-model="form.title" required />
            </div>
            <div class="space-y-2">
                <Label for="slug">Slug</Label>
                <Input id="slug" v-model="form.slug" required />
            </div>
            <div class="space-y-2">
                <Label for="body">Body (HTML)</Label>
                <textarea id="body" v-model="form.body" rows="10" class="flex w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-sm" />
            </div>
            <div class="space-y-2">
                <Label for="seo_title">SEO title</Label>
                <Input id="seo_title" v-model="form.seo_title" />
            </div>
            <div class="space-y-2">
                <Label for="seo_description">SEO description</Label>
                <Input id="seo_description" v-model="form.seo_description" />
            </div>
            <div class="flex items-center gap-2">
                <Checkbox id="is_published" v-model:checked="form.is_published" />
                <Label for="is_published">Published</Label>
            </div>
            <div class="flex gap-3">
                <Button type="submit" class="bg-teal-800 hover:bg-teal-900" :disabled="form.processing">Save</Button>
                <Button type="button" variant="destructive" @click="destroyPage">Delete</Button>
            </div>
        </form>
    </AdminLayout>
</template>
