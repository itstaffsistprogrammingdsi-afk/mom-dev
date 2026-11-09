<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Division, User } from '@/types';
import { HocuspocusProvider } from '@hocuspocus/provider';
import type { JSONContent } from '@tiptap/core';
import Collaboration from '@tiptap/extension-collaboration';
import CollaborationCursor from '@tiptap/extension-collaboration-cursor';
import Link from '@tiptap/extension-link';
import Mention from '@tiptap/extension-mention';
import Placeholder from '@tiptap/extension-placeholder';
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { AtSign, Bold, Heading2, Italic, Link2, List, ListOrdered, ListTodo, Redo2, Table2, Undo2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import * as Y from 'yjs';

interface Props {
    modelValue?: JSONContent | Record<string, unknown> | null;
    editable?: boolean;
    users?: Pick<User, 'id' | 'name'>[];
    divisions?: Division[];
    topics?: { id: number; name: string }[];
    collabUrl?: string | null;
    collabDocument?: string;
    collabToken?: string | null;
    collabUser?: string;
}

const props = withDefaults(defineProps<Props>(), {
    editable: true,
    users: () => [],
    divisions: () => [],
    topics: () => [],
    collabUrl: null,
});

const emit = defineEmits<{
    'update:modelValue': [value: JSONContent];
}>();

const ydoc = props.collabUrl ? new Y.Doc() : null;

const provider =
    props.collabUrl && ydoc
        ? new HocuspocusProvider({
              url: props.collabUrl,
              name: props.collabDocument ?? 'meeting',
              document: ydoc,
              token: props.collabToken ?? undefined,
          })
        : null;

const MentionNode = Mention.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            type: {
                default: 'person',
                parseHTML: (element: HTMLElement) => element.getAttribute('data-mention-type') ?? 'person',
                renderHTML: (attributes: Record<string, unknown>) => ({
                    'data-mention-type': attributes.type,
                }),
            },
        };
    },
});

const editor = useEditor({
    content: props.modelValue ?? { type: 'doc', content: [{ type: 'paragraph' }] },
    editable: props.editable,
    extensions: [
        StarterKit.configure({
            history: props.collabUrl ? false : undefined,
        }),
        Placeholder.configure({
            placeholder: 'Tulis pembahasan rapat di sini…',
        }),
        Link.configure({
            openOnClick: false,
            autolink: true,
        }),
        TaskList,
        TaskItem.configure({ nested: true }),
        Table.configure({ resizable: true }),
        TableRow,
        TableCell,
        TableHeader,
        MentionNode.configure({
            HTMLAttributes: { class: 'mention' },
        }),
        ...(ydoc && provider
            ? [
                  Collaboration.configure({ document: ydoc }),
                  CollaborationCursor.configure({
                      provider,
                      user: { name: props.collabUser ?? 'Pengguna', color: '#2563eb' },
                  }),
              ]
            : []),
    ],
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.getJSON());
    },
});

let seedTimer: ReturnType<typeof setTimeout> | null = null;
let contentSeeded = !provider;

function seedCollaborativeContent() {
    if (contentSeeded || !editor.value || !props.modelValue) {
        return;
    }

    if (editor.value.getText().trim().length > 0) {
        contentSeeded = true;

        return;
    }

    editor.value.commands.setContent(props.modelValue as JSONContent, false);
    contentSeeded = true;
}

if (provider) {
    provider.on('synced', () => {
        if (seedTimer) {
            clearTimeout(seedTimer);
        }

        seedTimer = setTimeout(seedCollaborativeContent, 150);
    });

    setTimeout(seedCollaborativeContent, 2500);
}

const tagSelection = ref('');

const tagOptions = computed(() => [
    ...props.users.map((user) => ({
        key: `person:${user.id}`,
        type: 'person',
        id: user.id,
        label: user.name,
    })),
    ...props.divisions.map((division) => ({
        key: `division:${division.id}`,
        type: 'division',
        id: division.id,
        label: division.name,
    })),
    ...props.topics.map((topic) => ({
        key: `topic:${topic.id}`,
        type: 'topic',
        id: topic.id,
        label: topic.name,
    })),
]);

function insertTag() {
    const selected = tagOptions.value.find((option) => option.key === tagSelection.value);

    if (!selected || !editor.value) {
        return;
    }

    editor.value
        .chain()
        .focus()
        .insertContent([
            { type: 'mention', attrs: { id: selected.id, label: selected.label, type: selected.type } },
            { type: 'text', text: ' ' },
        ])
        .run();

    tagSelection.value = '';
}

function setLink() {
    const url = window.prompt('Alamat tautan');

    if (!url) {
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value || props.collabUrl || !value) {
            return;
        }

        if (JSON.stringify(value) !== JSON.stringify(editor.value.getJSON())) {
            editor.value.commands.setContent(value as JSONContent, false);
        }
    },
    { deep: true },
);

watch(
    () => props.editable,
    (editable) => editor.value?.setEditable(editable),
);

onBeforeUnmount(() => {
    if (seedTimer) {
        clearTimeout(seedTimer);
    }

    provider?.destroy();
    ydoc?.destroy();
    editor.value?.destroy();
});

defineExpose({
    getJSON: () => editor.value?.getJSON(),
});
</script>

<template>
    <div class="overflow-hidden rounded-lg border bg-card">
        <div v-if="editor && editable" class="flex flex-wrap items-center gap-1 border-b bg-muted/40 p-2">
            <Button type="button" variant="ghost" size="icon" title="Tebal" @click="editor.chain().focus().toggleBold().run()">
                <Bold />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Miring" @click="editor.chain().focus().toggleItalic().run()">
                <Italic />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Judul" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
                <Heading2 />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Daftar" @click="editor.chain().focus().toggleBulletList().run()">
                <List />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Daftar bernomor" @click="editor.chain().focus().toggleOrderedList().run()">
                <ListOrdered />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Daftar tugas" @click="editor.chain().focus().toggleTaskList().run()">
                <ListTodo />
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                title="Sisipkan tabel"
                @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
            >
                <Table2 />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Tautan" @click="setLink">
                <Link2 />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Urungkan" @click="editor.chain().focus().undo().run()">
                <Undo2 />
            </Button>
            <Button type="button" variant="ghost" size="icon" title="Ulangi" @click="editor.chain().focus().redo().run()">
                <Redo2 />
            </Button>

            <div class="ml-auto flex items-center gap-1">
                <AtSign class="size-4 text-muted-foreground" />
                <select v-model="tagSelection" class="h-8 rounded-md border bg-background px-2 text-xs" @change="insertTag">
                    <option value="">Sisipkan tag…</option>
                    <optgroup label="Orang">
                        <option v-for="option in tagOptions.filter((item) => item.type === 'person')" :key="option.key" :value="option.key">
                            @{{ option.label }}
                        </option>
                    </optgroup>
                    <optgroup label="Divisi">
                        <option v-for="option in tagOptions.filter((item) => item.type === 'division')" :key="option.key" :value="option.key">
                            @{{ option.label }}
                        </option>
                    </optgroup>
                    <optgroup label="Topik">
                        <option v-for="option in tagOptions.filter((item) => item.type === 'topic')" :key="option.key" :value="option.key">
                            #{{ option.label }}
                        </option>
                    </optgroup>
                </select>
            </div>
        </div>

        <EditorContent :editor="editor" class="meeting-editor" />
    </div>
</template>
