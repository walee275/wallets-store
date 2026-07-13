<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import ProductCard from '@/components/storefront/ProductCard.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const jsonLdString = computed(() => JSON.stringify(props.jsonLd));

interface Variant {
    id: number;
    sku: string;
    price_cents: number;
    compare_at_cents?: number | null;
    stock_quantity: number;
    is_default: boolean;
    optionValues?: { id: number; value: string; optionType: { name: string } }[];
}

interface Review {
    id: number;
    rating: number;
    title?: string | null;
    body?: string | null;
    user?: { name: string };
    created_at: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    brand?: string | null;
    variants: Variant[];
    images: { id: number; path: string; alt?: string | null }[];
    reviews: Review[];
}

const props = defineProps<{
    product: Product;
    defaultVariant: Variant | null;
    relatedProducts: Product[];
    jsonLd: Record<string, unknown>;
}>();

const { formatMoney } = useFormatMoney();
const selectedVariantId = ref(props.defaultVariant?.id ?? props.product.variants[0]?.id ?? null);
const selectedImageIndex = ref(0);

const selectedVariant = computed(() => props.product.variants.find((v) => v.id === selectedVariantId.value) ?? null);

const cartForm = useForm({
    variant_id: selectedVariantId.value ?? 0,
    quantity: 1,
});

function addToCart() {
    if (!selectedVariant.value) return;
    cartForm.variant_id = selectedVariant.value.id;
    cartForm.post(route('cart.store'), { preserveScroll: true });
}

function selectVariant(variantId: number) {
    selectedVariantId.value = variantId;
}
</script>

<template>
    <Head :title="product.title">
        <component :is="'script'" type="application/ld+json">{{ jsonLdString }}</component>
    </Head>

    <StorefrontLayout>
        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                <div class="aspect-square overflow-hidden rounded-lg border border-stone-200 bg-white">
                    <img
                        v-if="product.images[selectedImageIndex]"
                        :src="storageUrl(product.images[selectedImageIndex].path)"
                        :alt="product.images[selectedImageIndex].alt ?? product.title"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div v-if="product.images.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                    <button
                        v-for="(image, index) in product.images"
                        :key="image.id"
                        type="button"
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-md border-2"
                        :class="index === selectedImageIndex ? 'border-teal-800' : 'border-stone-200'"
                        @click="selectedImageIndex = index"
                    >
                        <img :src="storageUrl(image.path)" :alt="image.alt ?? ''" class="h-full w-full object-cover" />
                    </button>
                </div>
            </div>

            <div>
                <p v-if="product.brand" class="text-sm text-stone-500">{{ product.brand }}</p>
                <h1 class="text-2xl font-semibold">{{ product.title }}</h1>

                <div v-if="selectedVariant" class="mt-3 flex items-baseline gap-2">
                    <span class="text-xl font-semibold text-teal-800">{{ formatMoney(selectedVariant.price_cents) }}</span>
                    <span
                        v-if="selectedVariant.compare_at_cents && selectedVariant.compare_at_cents > selectedVariant.price_cents"
                        class="text-sm text-stone-400 line-through"
                    >
                        {{ formatMoney(selectedVariant.compare_at_cents) }}
                    </span>
                </div>

                <div v-if="product.variants.length > 1" class="mt-6 space-y-2">
                    <Label>Variant</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="rounded-md border px-3 py-1.5 text-sm transition"
                            :class="variant.id === selectedVariantId ? 'border-teal-800 bg-teal-50 text-teal-900' : 'border-stone-200 hover:border-stone-300'"
                            :disabled="variant.stock_quantity <= 0"
                            @click="selectVariant(variant.id)"
                        >
                            {{ variant.optionValues?.map((o) => o.value).join(' / ') || variant.sku }}
                        </button>
                    </div>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="addToCart">
                    <div class="flex items-end gap-3">
                        <div class="space-y-2">
                            <Label for="quantity">Quantity</Label>
                            <input
                                id="quantity"
                                v-model.number="cartForm.quantity"
                                type="number"
                                min="1"
                                :max="selectedVariant?.stock_quantity ?? 1"
                                class="flex h-9 w-20 rounded-md border border-input bg-background px-3 text-sm"
                            />
                        </div>
                        <Button
                            type="submit"
                            class="bg-teal-800 hover:bg-teal-900"
                            :disabled="cartForm.processing || !selectedVariant || selectedVariant.stock_quantity <= 0"
                        >
                            {{ selectedVariant && selectedVariant.stock_quantity <= 0 ? 'Out of stock' : 'Add to cart' }}
                        </Button>
                    </div>
                    <InputError :message="cartForm.errors.cart" />
                </form>

                <div v-if="product.description" class="prose prose-stone mt-8 max-w-none text-sm" v-html="product.description" />
            </div>
        </div>

        <section v-if="product.reviews.length" class="mt-12">
            <h2 class="mb-4 text-lg font-semibold">Reviews</h2>
            <div class="space-y-4">
                <article v-for="review in product.reviews" :key="review.id" class="rounded-lg border border-stone-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ review.user?.name ?? 'Customer' }}</span>
                        <span class="text-sm text-amber-600">{{ '★'.repeat(review.rating) }}</span>
                    </div>
                    <p v-if="review.title" class="mt-1 text-sm font-medium">{{ review.title }}</p>
                    <p v-if="review.body" class="mt-1 text-sm text-stone-600">{{ review.body }}</p>
                </article>
            </div>
        </section>

        <section v-if="relatedProducts.length" class="mt-12">
            <h2 class="mb-4 text-lg font-semibold">Related products</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <ProductCard v-for="related in relatedProducts" :key="related.id" :product="related" />
            </div>
        </section>
    </StorefrontLayout>
</template>
