<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormatMoney } from '@/composables/useFormatMoney';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Address {
    id: number;
    name: string;
    phone?: string | null;
    line1: string;
    line2?: string | null;
    city: string;
    state?: string | null;
    postal_code?: string | null;
    country: string;
    is_default?: boolean;
}

interface ShippingRate {
    id: number;
    name: string;
    price_cents: number;
    zone?: { name: string };
}

interface PaymentMethod {
    id: number;
    driver: string;
    name: string;
}

interface CartItem {
    id: number;
    quantity: number;
    variant: { price_cents: number; product: { title: string } };
}

const props = defineProps<{
    cart: { items: CartItem[] };
    shippingRates: ShippingRate[];
    paymentMethods: PaymentMethod[];
    addresses: Address[];
    checkout: {
        address: Record<string, string> | null;
        shipping_rate_id: number | null;
        discount_code: string | null;
    };
    totals: {
        subtotal_cents: number;
        discount_cents: number;
        shipping_cents: number;
        tax_cents: number;
        total_cents: number;
    };
}>();

const page = usePage();
const { formatMoney } = useFormatMoney();
const isGuest = computed(() => !page.props.auth?.user);

const addressForm = useForm({
    name: props.checkout.address?.name ?? '',
    phone: props.checkout.address?.phone ?? '',
    line1: props.checkout.address?.line1 ?? '',
    line2: props.checkout.address?.line2 ?? '',
    city: props.checkout.address?.city ?? '',
    state: props.checkout.address?.state ?? '',
    postal_code: props.checkout.address?.postal_code ?? '',
    country: props.checkout.address?.country ?? 'PK',
});

const defaultShippingRateId: number | null =
    props.checkout.shipping_rate_id ?? props.shippingRates[0]?.id ?? null;

const shippingForm = useForm<{ shipping_rate_id: number | null }>({
    shipping_rate_id: defaultShippingRateId,
});

const couponForm = useForm({
    code: props.checkout.discount_code ?? '',
});

const placeForm = useForm<{
    email: string;
    payment_driver: string;
    shipping_rate_id: number | null;
    billing_same_as_shipping: boolean;
    notes: string;
}>({
    email: '',
    payment_driver: props.paymentMethods[0]?.driver ?? '',
    shipping_rate_id: defaultShippingRateId,
    billing_same_as_shipping: true,
    notes: '',
});

watch(
    () => shippingForm.shipping_rate_id,
    (rateId) => {
        placeForm.shipping_rate_id = rateId == null ? null : Number(rateId);
    },
);

function saveAddress() {
    addressForm.post(route('checkout.address'), { preserveScroll: true });
}

function saveShipping() {
    if (shippingForm.shipping_rate_id != null) {
        shippingForm.shipping_rate_id = Number(shippingForm.shipping_rate_id);
    }

    shippingForm.post(route('checkout.shipping'), { preserveScroll: true });
}

function onShippingRateChange() {
    saveShipping();
}

function applyCoupon() {
    couponForm.post(route('checkout.coupon'), { preserveScroll: true });
}

function placeOrder() {
    if (shippingForm.shipping_rate_id != null) {
        placeForm.shipping_rate_id = Number(shippingForm.shipping_rate_id);
    }

    placeForm.post(route('checkout.place'));
}

function fillAddress(address: Address) {
    addressForm.name = address.name;
    addressForm.phone = address.phone ?? '';
    addressForm.line1 = address.line1;
    addressForm.line2 = address.line2 ?? '';
    addressForm.city = address.city;
    addressForm.state = address.state ?? '';
    addressForm.postal_code = address.postal_code ?? '';
    addressForm.country = address.country;
}
</script>

<template>

    <Head title="Checkout" />

    <StorefrontLayout>
        <h1 class="mb-6 text-2xl font-semibold">Checkout</h1>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <div class="space-y-8">
                <!-- Address -->
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Shipping address</h2>

                    <div v-if="addresses.length" class="mt-3 flex flex-wrap gap-2">
                        <button v-for="address in addresses" :key="address.id" type="button"
                            class="rounded-md border border-stone-200 px-3 py-1.5 text-xs hover:border-teal-800"
                            @click="fillAddress(address)">
                            {{ address.name }} — {{ address.city }}
                        </button>
                    </div>

                    <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="saveAddress">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="name">Full name</Label>
                            <Input id="name" v-model="addressForm.name" required />
                            <InputError :message="addressForm.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="addressForm.phone" />
                        </div>
                        <div class="space-y-2">
                            <Label for="country">Country</Label>
                            <Input id="country" v-model="addressForm.country" maxlength="2" required />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="line1">Address line 1</Label>
                            <Input id="line1" v-model="addressForm.line1" required />
                            <InputError :message="addressForm.errors.line1" />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="line2">Address line 2</Label>
                            <Input id="line2" v-model="addressForm.line2" />
                        </div>
                        <div class="space-y-2">
                            <Label for="city">City</Label>
                            <Input id="city" v-model="addressForm.city" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="state">State</Label>
                            <Input id="state" v-model="addressForm.state" />
                        </div>
                        <div class="space-y-2">
                            <Label for="postal_code">Postal code</Label>
                            <Input id="postal_code" v-model="addressForm.postal_code" />
                        </div>
                        <div class="sm:col-span-2">
                            <Button type="submit" variant="outline" :disabled="addressForm.processing">Save
                                address</Button>
                        </div>
                    </form>
                </section>

                <!-- Payment & place order -->
                <section class="rounded-lg border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Payment</h2>
                    <form class="mt-4 space-y-4" @submit.prevent="placeOrder">
                        <div v-if="isGuest" class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="placeForm.email" type="email" required />
                            <InputError :message="placeForm.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label>Payment method</Label>
                            <label v-for="method in paymentMethods" :key="method.id"
                                class="flex cursor-pointer items-center gap-3 rounded-md border border-stone-200 px-4 py-3"
                                :class="placeForm.payment_driver === method.driver ? 'border-teal-800 bg-teal-50' : ''">
                                <input v-model="placeForm.payment_driver" type="radio" :value="method.driver" />
                                {{ method.name }}
                            </label>
                            <InputError :message="placeForm.errors.payment_driver" />
                        </div>

                        <div class="space-y-2">
                            <Label for="notes">Order notes (optional)</Label>
                            <textarea id="notes" v-model="placeForm.notes" rows="3"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        </div>

                        <InputError :message="placeForm.errors.checkout" />
                        <InputError :message="placeForm.errors.address" />

                        <Button type="submit" class="w-full bg-teal-800 hover:bg-teal-900"
                            :disabled="placeForm.processing">
                            Place order
                        </Button>
                    </form>
                </section>
            </div>

            <aside class="h-fit rounded-lg border border-stone-200 bg-white p-5">
                <!-- Shipping -->
                <section class="rounded-lg mb-3 border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Shipping method</h2>
                    <div class="mt-4 space-y-3">
                        <label v-for="rate in shippingRates" :key="rate.id"
                            class="flex cursor-pointer items-center justify-between rounded-md border border-stone-200 px-4 py-3"
                            :class="Number(shippingForm.shipping_rate_id) === rate.id ? 'border-teal-800 bg-teal-50' : ''">
                            <span class="flex items-center gap-3">
                                <input v-model="shippingForm.shipping_rate_id" type="radio" :value="rate.id"
                                    :disabled="shippingForm.processing" @change="onShippingRateChange" />
                                <span>
                                    <span class="font-medium">{{ rate.name }}</span>
                                    <span v-if="rate.zone" class="ml-2 text-xs text-stone-500">{{ rate.zone.name
                                    }}</span>
                                </span>
                            </span>
                            <span class="text-sm font-medium">{{ formatMoney(rate.price_cents) }}</span>
                        </label>
                        <InputError :message="shippingForm.errors.shipping_rate_id" />
                        <InputError :message="placeForm.errors.shipping_rate_id" />
                    </div>
                </section>

                <!-- Coupon -->
                <section class="rounded-lg mb-3 border border-stone-200 bg-white p-5">
                    <h2 class="font-semibold">Discount code</h2>
                    <form class="mt-4 flex gap-2" @submit.prevent="applyCoupon">
                        <Input v-model="couponForm.code" placeholder="Enter code" class="flex-1" />
                        <Button type="submit" variant="outline" :disabled="couponForm.processing">Apply</Button>
                    </form>
                    <InputError :message="couponForm.errors.code" />
                    <p v-if="checkout.discount_code" class="mt-2 text-sm text-teal-800">Applied: {{
                        checkout.discount_code }}</p>
                </section>
                <div>
                    <h2 class="font-semibold">Order summary</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li v-for="item in cart.items" :key="item.id" class="flex justify-between gap-2">
                            <span class="line-clamp-1">{{ item.variant.product.title }} × {{ item.quantity }}</span>
                            <span>{{ formatMoney(item.quantity * item.variant.price_cents) }}</span>
                        </li>
                    </ul>
                    <dl class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                        <div class="flex justify-between">
                            <dt>Subtotal</dt>
                            <dd>{{ formatMoney(totals.subtotal_cents) }}</dd>
                        </div>
                        <div v-if="totals.discount_cents" class="flex justify-between text-teal-800">
                            <dt>Discount</dt>
                            <dd>-{{ formatMoney(totals.discount_cents) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Shipping</dt>
                            <dd>{{ formatMoney(totals.shipping_cents) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Tax</dt>
                            <dd>{{ formatMoney(totals.tax_cents) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold">
                            <dt>Total</dt>
                            <dd>{{ formatMoney(totals.total_cents) }}</dd>
                        </div>
                    </dl>
                </div>

            </aside>
        </div>
    </StorefrontLayout>
</template>
