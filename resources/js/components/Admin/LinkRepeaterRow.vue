<script setup lang="ts">
export interface FooterLinkItem {
    label: string;
    type: 'page' | 'url' | 'route';
    value: string;
}

const props = defineProps<{
    link: FooterLinkItem;
    index: number;
}>();

const emit = defineEmits<{
    remove: [];
    dragStart: [index: number];
    dragOver: [index: number];
    drop: [index: number];
}>();
</script>

<template>
    <div
        class="grid grid-cols-[auto_1.3fr_1fr_1.3fr_auto] items-center gap-2.5 border-b border-stone-100 py-2.5 last:border-b-0"
        draggable="true"
        @dragstart="emit('dragStart', props.index)"
        @dragover.prevent="emit('dragOver', props.index)"
        @drop.prevent="emit('drop', props.index)"
    >
        <span class="cursor-grab select-none px-1 text-sm text-stone-300 active:cursor-grabbing" title="Drag to reorder" aria-hidden="true">
            ⠿
        </span>
        <input
            v-model="link.label"
            type="text"
            placeholder="Label"
            class="h-8 rounded-md border border-stone-300 bg-white px-2.5 text-[13px] outline-none focus:border-[#8C5A2B] focus:ring-2 focus:ring-[#8C5A2B]/20"
        />
        <select
            v-model="link.type"
            class="h-8 rounded-md border border-stone-300 bg-white px-2.5 text-[13px] outline-none focus:border-[#8C5A2B] focus:ring-2 focus:ring-[#8C5A2B]/20"
        >
            <option value="page">CMS page</option>
            <option value="route">Route name</option>
            <option value="url">External URL</option>
        </select>
        <input
            v-model="link.value"
            type="text"
            placeholder="Target"
            class="h-8 rounded-md border border-stone-300 bg-white px-2.5 text-[13px] outline-none focus:border-[#8C5A2B] focus:ring-2 focus:ring-[#8C5A2B]/20"
        />
        <button
            type="button"
            class="flex h-[30px] w-[30px] items-center justify-center rounded-md border border-stone-200 bg-white text-sm text-stone-500 transition-colors hover:border-red-400 hover:text-red-600"
            aria-label="Remove link"
            @click="emit('remove')"
        >
            ✕
        </button>
    </div>
</template>
