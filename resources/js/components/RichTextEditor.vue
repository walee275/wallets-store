<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Undo2,
} from 'lucide-vue-next';
import { onBeforeUnmount, watch } from 'vue';

const model = defineModel<string>({ default: '' });

const props = withDefaults(
    defineProps<{
        id?: string;
        placeholder?: string;
        class?: string;
    }>(),
    {
        placeholder: 'Write page content…',
    },
);

function normalizeHtml(html: string): string {
    const trimmed = html.trim();
    if (trimmed === '' || trimmed === '<p></p>' || trimmed === '<p><br></p>') {
        return '';
    }
    return trimmed;
}

const editor = useEditor({
    content: model.value || '',
    extensions: [
        StarterKit,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-teal-800 underline underline-offset-2',
            },
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            ...(props.id ? { id: props.id } : {}),
            class: 'prose prose-stone max-w-none min-h-[12rem] px-3 py-2 text-sm focus:outline-none',
        },
    },
    onUpdate: ({ editor: current }) => {
        model.value = normalizeHtml(current.getHTML());
    },
});

watch(model, (value) => {
    if (!editor.value) {
        return;
    }

    const next = value || '';
    if (normalizeHtml(editor.value.getHTML()) !== normalizeHtml(next)) {
        editor.value.commands.setContent(next, { emitUpdate: false });
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function toggleLink() {
    if (!editor.value) {
        return;
    }

    if (editor.value.isActive('link')) {
        editor.value.chain().focus().unsetLink().run();
        return;
    }

    const previous = editor.value.getAttributes('link').href as string | undefined;
    const url = window.prompt('Enter URL', previous ?? 'https://');

    if (url === null) {
        return;
    }

    const trimmed = url.trim();
    if (trimmed === '') {
        editor.value.chain().focus().unsetLink().run();
        return;
    }

    editor.value.chain().focus().extendMarkRange('link').setLink({ href: trimmed }).run();
}
</script>

<template>
    <div :class="cn('overflow-hidden rounded-md border border-input bg-background', props.class)">
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 border-b border-input bg-muted/40 p-1">
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('bold') }"
                title="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('italic') }"
                title="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('heading', { level: 2 }) }"
                title="Heading 2"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('heading', { level: 3 }) }"
                title="Heading 3"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('bulletList') }"
                title="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('orderedList') }"
                title="Numbered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('blockquote') }"
                title="Quote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                :class="{ 'bg-accent text-accent-foreground': editor.isActive('link') }"
                title="Link"
                @click="toggleLink"
            >
                <LinkIcon class="h-4 w-4" />
            </Button>
            <div class="mx-1 h-5 w-px bg-border" />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                title="Undo"
                :disabled="!editor.can().undo()"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 class="h-4 w-4" />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8"
                title="Redo"
                :disabled="!editor.can().redo()"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 class="h-4 w-4" />
            </Button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
    color: hsl(var(--muted-foreground));
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.tiptap h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
}

.tiptap h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0.75rem 0 0.5rem;
}

.tiptap p {
    margin: 0.4rem 0;
}

.tiptap ul {
    list-style: disc;
    padding-left: 1.25rem;
    margin: 0.5rem 0;
}

.tiptap ol {
    list-style: decimal;
    padding-left: 1.25rem;
    margin: 0.5rem 0;
}

.tiptap blockquote {
    border-left: 3px solid hsl(var(--border));
    margin: 0.75rem 0;
    padding-left: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.tiptap a {
    color: #115e59;
    text-decoration: underline;
    text-underline-offset: 2px;
}
</style>
