<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { storageUrl } from '@/lib/storage';
import { Head, Link, router } from '@inertiajs/vue3';

interface CartItem {
    id: number;
    quantity: number;
    variant: {
        id: number;
        price_cents: number;
        product: {
            title: string;
            slug: string;
            images: { path: string; alt?: string | null }[];
        };
    };
}

interface Cart {
    id: number;
    items: CartItem[];
}

defineProps<{
    cart: Cart;
}>();

const { formatMoney } = useFormatMoney();

function updateQuantity(item: CartItem, quantity: number) {
    router.patch(route('cart.update', item.id), { quantity }, { preserveScroll: true });
}

function removeItem(item: CartItem) {
    router.delete(route('cart.destroy', item.id), { preserveScroll: true });
}

function lineTotal(item: CartItem): number {
    return item.quantity * item.variant.price_cents;
}

function cartSubtotal(items: CartItem[]): number {
    return items.reduce((sum, item) => sum + lineTotal(item), 0);
}
</script>

<template>
    <Head title="Cart" />

    <StorefrontLayout>
        <h1 class="mb-6 text-2xl font-semibold">Shopping cart</h1>

        <div v-if="cart.items.length" class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="space-y-4">
                <article
                    v-for="item in cart.items"
                    :key="item.id"
                    class="flex gap-4 rounded-lg border border-stone-200 bg-white p-4"
                >
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-stone-100">
                        <img
                            v-if="item.variant.product.images[0]"
                            :src="storageUrl(item.variant.product.images[0].path)"
                            :alt="item.variant.product.images[0].alt ?? item.variant.product.title"
                            class="h-full w-full object-cover"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link :href="route('catalog.show', item.variant.product.slug)" class="font-medium hover:text-teal-800">
                            {{ item.variant.product.title }}
                        </Link>
                        <p class="text-sm text-stone-600">{{ formatMoney(item.variant.price_cents) }} each</p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <input
                                :value="item.quantity"
                                type="number"
                                min="1"
                                class="h-8 w-16 rounded-md border border-input px-2 text-sm"
                                @change="updateQuantity(item, Number(($event.target as HTMLInputElement).value))"
                            />
                            <button type="button" class="text-sm text-red-600 hover:underline" @click="removeItem(item)">
                                Remove
                            </button>
                        </div>
                    </div>
                    <p class="font-medium">{{ formatMoney(lineTotal(item)) }}</p>
                </article>
            </div>

            <aside class="h-fit rounded-lg border border-stone-200 bg-white p-5">
                <h2 class="font-semibold">Order summary</h2>
                <div class="mt-4 flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>{{ formatMoney(cartSubtotal(cart.items)) }}</span>
                </div>
                <Button as-child class="mt-6 w-full bg-teal-800 hover:bg-teal-900">
                    <Link :href="route('checkout.show')">Proceed to checkout</Link>
                </Button>
            </aside>
        </div>

        <div v-else class="rounded-lg border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
            <p class="text-stone-500">Your cart is empty.</p>
            <Button as-child class="mt-4 bg-teal-800 hover:bg-teal-900">
                <Link :href="route('catalog.index')">Continue shopping</Link>
            </Button>
        </div>

    </StorefrontLayout>
</template>
