<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        existingUrl?: string | null;
        remove?: boolean;
        hint?: string;
        accept?: string;
    }>(),
    {
        existingUrl: null,
        remove: false,
        hint: 'Upload a file or drag and drop',
        accept: 'image/*',
    },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
    'update:remove': [value: boolean];
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const isDragging = ref(false);

const displayUrl = computed(() => {
    if (props.remove) {
        return null;
    }

    return previewUrl.value ?? props.existingUrl ?? null;
});

watch(
    () => props.modelValue,
    (file) => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }

        if (file) {
            previewUrl.value = URL.createObjectURL(file);
        }
    },
);

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

function openPicker() {
    inputRef.value?.click();
}

function assignFile(file: File | null) {
    emit('update:modelValue', file);
    emit('update:remove', false);
}

function onFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    assignFile(file);
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;

    if (file && file.type.startsWith('image/')) {
        assignFile(file);
    }
}

function clearFile() {
    assignFile(null);
    emit('update:remove', true);

    if (inputRef.value) {
        inputRef.value.value = '';
    }
}
</script>

<template>
    <div>
        <div
            class="flex items-center gap-3.5 rounded-lg border border-dashed bg-stone-50 p-4 transition-colors"
            :class="isDragging ? 'border-[#8C5A2B] bg-[#F3ECE3]/40' : 'border-stone-300'"
            @dragenter.prevent="isDragging = true"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-md bg-stone-200 text-[10px] text-stone-500"
            >
                <img v-if="displayUrl" :src="displayUrl" alt="Preview" class="h-full w-full object-contain" />
                <span v-else>No file</span>
            </div>
            <div class="min-w-0 flex-1 text-[12.5px] text-stone-500">
                <button type="button" class="font-semibold text-[#7A4B22] hover:underline" @click="openPicker">
                    Upload a file
                </button>
                <span> or drag and drop</span>
                <p v-if="hint" class="mt-0.5">{{ hint }}</p>
            </div>
            <input ref="inputRef" type="file" class="hidden" :accept="accept" @change="onFileChange" />
        </div>
        <button
            v-if="displayUrl || existingUrl"
            type="button"
            class="mt-2 text-xs font-medium text-stone-500 hover:text-red-600"
            @click="clearFile"
        >
            Remove image
        </button>
    </div>
</template>
