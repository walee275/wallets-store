<script setup lang="ts">
import CategoryTile from '@/components/Storefront/CategoryTile.vue';
import ProductCard from '@/components/Storefront/ProductCard.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CategoryChild {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    children?: CategoryChild[];
}

interface Collection {
    id: number;
    name: string;
    slug?: string;
}

interface Product {
    id: number;
    title: string;
    slug: string;
    brand?: string | null;
    default_variant?: { id: number; price_cents: number; compare_at_cents?: number | null } | null;
    defaultVariant?: { id: number; price_cents: number; compare_at_cents?: number | null } | null;
    primary_image?: { path: string; alt?: string | null } | null;
    primaryImage?: { path: string; alt?: string | null } | null;
}

const props = defineProps<{
    featuredCollection: Collection | null;
    featuredProducts: Product[];
    categories: Category[];
}>();

const tileVariants = ['cognac', 'espresso', 'oxblood'] as const;

const collectionHref = computed(() => route('catalog.index'));
</script>

<template>
    <Head title="Home" />

    <StorefrontLayout full-bleed>
        <!-- HERO — craft thesis, not a discount banner -->
        <section
            class="relative flex min-h-[560px] items-end overflow-hidden bg-[radial-gradient(ellipse_900px_500px_at_15%_20%,rgba(140,90,43,0.35),transparent_60%),radial-gradient(ellipse_700px_500px_at_85%_80%,rgba(92,31,34,0.28),transparent_55%),repeating-radial-gradient(circle_at_50%_50%,rgba(0,0,0,0.05)_0,rgba(0,0,0,0.05)_1px,transparent_2px,transparent_4px),linear-gradient(160deg,#241811_0%,#2E2016_45%,#241610_100%)]"
        >
            <div class="absolute inset-0 bg-[linear-gradient(0deg,rgba(20,13,9,0.55)_0%,rgba(20,13,9,0)_45%)]" />
            <div class="relative z-[2] mx-auto w-full max-w-[1240px] px-6 pb-16 pt-20 md:px-10 md:pb-16">
                <p class="mb-[22px] font-mono text-xs uppercase tracking-[3px] text-brass">
                    Full-Grain &nbsp;·&nbsp; Vegetable-Tanned &nbsp;·&nbsp; Hand-Stitched
                </p>
                <h1 class="max-w-[640px] font-display text-[38px] font-[450] leading-[1.05] tracking-[-0.5px] text-canvas md:text-[58px]">
                    Cut once.<br />
                    Carried for decades.
                </h1>
                <p class="mt-[22px] max-w-[460px] text-base leading-[26px] text-[#C9BEA8]">
                    Every wallet begins as a single hide, selected by hand and stitched saddle-tight by our small workshop in Islamabad — built to age
                    into something better than new.
                </p>
                <div class="mt-[34px] flex flex-wrap items-center gap-7">
                    <Link
                        :href="collectionHref"
                        class="bg-cognac px-[34px] py-[15px] text-[13px] font-semibold uppercase tracking-[1.5px] text-[#F5EFE3] transition-colors hover:bg-[#7A4B22]"
                    >
                        Shop the Foundry Collection
                    </Link>
                    <a href="#craft" class="border-b border-brass pb-[3px] text-[13px] tracking-[0.5px] text-canvas"> See how it's made → </a>
                </div>
            </div>
        </section>

        <!-- SHOP BY CATEGORY -->
        <section v-if="categories.length" class="py-[84px]">
            <div class="mx-auto mb-10 max-w-[1240px] px-6 md:px-10">
                <p class="mb-2.5 font-mono text-[11px] uppercase tracking-[2.5px] text-atelier-stone">Shop by category</p>
                <h2 class="font-display text-[32px] font-[450] text-ink">Three forms, one standard</h2>
            </div>
            <div class="grid grid-cols-1 gap-0.5 bg-hairline sm:grid-cols-2 md:grid-cols-3">
                <CategoryTile
                    v-for="(category, index) in categories"
                    :key="category.id"
                    :category="category"
                    :variant="tileVariants[index % tileVariants.length]!"
                />
            </div>
        </section>

        <!-- BESTSELLERS -->
        <section class="py-[84px]">
            <div class="mx-auto max-w-[1240px] px-6 md:px-10">
                <div class="mb-10 flex items-baseline justify-between gap-4">
                    <div>
                        <p class="mb-2.5 font-mono text-[11px] uppercase tracking-[2.5px] text-atelier-stone">Most carried</p>
                        <h2 class="font-display text-[32px] font-[450] text-ink">
                            {{ featuredCollection?.name ?? 'Bestsellers' }}
                        </h2>
                    </div>
                    <Link :href="route('catalog.index')" class="shrink-0 border-b border-oxblood pb-0.5 text-[13px] text-oxblood"> View all → </Link>
                </div>

                <div v-if="featuredProducts.length" class="grid grid-cols-2 gap-5 md:grid-cols-3 md:gap-8 lg:grid-cols-4">
                    <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
                </div>
                <div v-else class="border border-dashed border-hairline px-6 py-12 text-center">
                    <p class="text-atelier-stone">No featured products yet.</p>
                    <Link :href="route('catalog.index')" class="mt-3 inline-block border-b border-cognac pb-0.5 text-sm text-cognac">
                        Browse catalog
                    </Link>
                </div>
            </div>
        </section>

        <!-- CRAFT STRIP -->
        <section id="craft" class="bg-espresso py-[70px] text-canvas">
            <div class="mx-auto grid max-w-[1240px] grid-cols-1 gap-12 px-6 md:grid-cols-3 md:gap-[50px] md:px-10">
                <div>
                    <p class="font-mono text-xs tracking-[2px] text-brass">01 — Select</p>
                    <h3 class="mt-3.5 font-display text-xl font-[450]">One hide, hand-chosen</h3>
                    <p class="mt-2.5 text-sm leading-[22px] text-[#B7A98C]">
                        Every piece starts with a single full-grain hide, inspected for grain consistency before a single cut is made.
                    </p>
                </div>
                <div>
                    <p class="font-mono text-xs tracking-[2px] text-brass">02 — Cut &amp; Stitch</p>
                    <h3 class="mt-3.5 font-display text-xl font-[450]">Saddle-stitched by hand</h3>
                    <p class="mt-2.5 text-sm leading-[22px] text-[#B7A98C]">
                        Our workshop hand-stitches every seam using waxed thread — slower than machine stitching, and far stronger.
                    </p>
                </div>
                <div>
                    <p class="font-mono text-xs tracking-[2px] text-brass">03 — Finish</p>
                    <h3 class="mt-3.5 font-display text-xl font-[450]">Edges burnished, not painted</h3>
                    <p class="mt-2.5 text-sm leading-[22px] text-[#B7A98C]">
                        Edges are burnished with beeswax rather than coated, so they age gracefully instead of peeling.
                    </p>
                </div>
            </div>
        </section>
    </StorefrontLayout>
</template>
