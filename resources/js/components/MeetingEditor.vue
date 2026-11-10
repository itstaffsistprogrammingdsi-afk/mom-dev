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
import { AtSign, Bold, Heading2, Italic, Link2, List, ListOrdered, ListTodo, Mic, MicOff, Redo2, Table2, Undo2 } from 'lucide-vue-next';
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
    showTagPicker?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    editable: true,
    users: () => [],
    divisions: () => [],
    topics: () => [],
    collabUrl: null,
    showTagPicker: true,
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

interface MentionItem {
    id: number;
    label: string;
    type: 'person' | 'division' | 'topic';
    hint: string;
}

function personAndDivisionItems(query: string): MentionItem[] {
    const normalized = query.trim().toLowerCase();

    const people = props.users
        .filter((user) => user.name.toLowerCase().includes(normalized))
        .map((user) => ({ id: user.id, label: user.name, type: 'person' as const, hint: 'Orang' }));

    const divisions = props.divisions
        .filter((division) => division.name.toLowerCase().includes(normalized))
        .map((division) => ({ id: division.id, label: division.name, type: 'division' as const, hint: 'Divisi' }));

    return [...people, ...divisions];
}

function topicItems(query: string): MentionItem[] {
    const normalized = query.trim().toLowerCase();

    return props.topics
        .filter((topic) => topic.name.toLowerCase().includes(normalized))
        .map((topic) => ({ id: topic.id, label: topic.name, type: 'topic' as const, hint: 'Topik' }));
}

function escapeHtml(value: string): string {
    return value.replace(/[&<>"']/g, (character) => {
        const entities: Record<string, string> = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return entities[character] ?? character;
    });
}

function createSuggestion(getItems: (query: string) => MentionItem[], char: '@' | '#') {
    return {
        char,
        items: ({ query }: { query: string }) => getItems(query).slice(0, 8),
        render: () => {
            let popup: HTMLDivElement | null = null;
            let items: MentionItem[] = [];
            let selected = 0;
            let command: ((item: MentionItem) => void) | null = null;

            const destroy = () => {
                popup?.remove();
                popup = null;
            };

            const select = (item: MentionItem | undefined) => {
                if (!item) {
                    return;
                }

                command?.(item);
                destroy();
            };

            const paint = () => {
                if (!popup) {
                    return;
                }

                popup.innerHTML =
                    items.length === 0
                        ? '<div class="mention-suggestion-empty">Tidak ada hasil</div>'
                        : items
                              .map(
                                  (item, index) => `
                                    <button type="button" role="option" aria-selected="${index === selected}" data-selected="${index === selected}" data-index="${index}" class="mention-suggestion-item">
                                        <span class="mention-suggestion-prefix">${char}</span>
                                        <span class="truncate">${escapeHtml(item.label)}</span>
                                        <span class="mention-suggestion-hint">${escapeHtml(item.hint)}</span>
                                    </button>
                                `,
                              )
                              .join('');

                popup.querySelectorAll<HTMLButtonElement>('[data-index]').forEach((button) => {
                    button.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        select(items[Number(button.dataset.index)]);
                    });
                });
            };

            const position = (rect: DOMRect | null) => {
                if (!popup) {
                    return;
                }

                let fallback: DOMRect | null = null;

                try {
                    const selection = window.getSelection();

                    if (selection && selection.rangeCount > 0) {
                        fallback = selection.getRangeAt(0).getBoundingClientRect();
                    }
                } catch {
                    fallback = null;
                }

                const bounds = rect ?? fallback;

                if (!bounds) {
                    return;
                }

                popup.style.top = `${Math.min(bounds.bottom + window.scrollY + 6, window.innerHeight + window.scrollY - 260)}px`;
                popup.style.left = `${Math.min(bounds.left + window.scrollX, window.innerWidth - 340)}px`;
            };

            return {
                onStart: (props: any) => {
                    items = Array.isArray(props.items) ? props.items : [];
                    command = props.command;
                    selected = 0;
                    popup = document.createElement('div');
                    popup.className = 'mention-suggestion';
                    popup.setAttribute('role', 'listbox');
                    popup.setAttribute('aria-label', char === '@' ? 'Pilih orang atau divisi' : 'Pilih topik');
                    document.body.appendChild(popup);
                    paint();
                    position(props.clientRect?.() ?? null);
                },
                onUpdate: (props: any) => {
                    items = Array.isArray(props.items) ? props.items : [];
                    command = props.command;
                    selected = Math.max(0, Math.min(selected, items.length - 1));
                    paint();
                    position(props.clientRect?.() ?? null);
                },
                onKeyDown: (props: any) => {
                    if (!popup) {
                        return false;
                    }

                    if (props.event.key === 'ArrowDown') {
                        selected = (selected + 1) % Math.max(items.length, 1);
                        paint();

                        return true;
                    }

                    if (props.event.key === 'ArrowUp') {
                        selected = (selected - 1 + items.length) % Math.max(items.length, 1);
                        paint();

                        return true;
                    }

                    if (props.event.key === 'Enter') {
                        select(items[selected]);

                        return true;
                    }

                    if (props.event.key === 'Escape') {
                        destroy();

                        return true;
                    }

                    return false;
                },
                onExit: destroy,
            };
        },
    };
}

const TopicMentionNode = MentionNode.extend({ name: 'topicMention' });

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
            suggestion: createSuggestion(personAndDivisionItems, '@') as any,
        }),
        TopicMentionNode.configure({
            HTMLAttributes: { class: 'mention' },
            suggestion: createSuggestion(topicItems, '#') as any,
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

const speechSupported = typeof window !== 'undefined' && ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window);
const isListening = ref(false);
const speechError = ref<string | null>(null);
let recognition: any = null;

function startDictation() {
    const SpeechRecognition = (window as any).SpeechRecognition ?? (window as any).webkitSpeechRecognition;

    if (!SpeechRecognition) {
        speechError.value = 'Browser ini belum mendukung dikte suara. Gunakan Chrome atau Edge.';

        return;
    }

    speechError.value = null;
    recognition = new SpeechRecognition();
    recognition.lang = 'id-ID';
    recognition.continuous = true;
    recognition.interimResults = true;

    recognition.onstart = () => {
        isListening.value = true;
    };

    recognition.onresult = (event: any) => {
        let finalText = '';

        for (let index = event.resultIndex; index < event.results.length; index++) {
            if (event.results[index].isFinal) {
                finalText += event.results[index][0].transcript;
            }
        }

        if (finalText.trim() !== '' && editor.value) {
            editor.value.chain().focus().insertContent(`${finalText.trim()} `).run();
        }
    };

    recognition.onerror = (event: any) => {
        speechError.value =
            event.error === 'not-allowed'
                ? 'Akses mikrofon ditolak. Izinkan mikrofon di pengaturan browser.'
                : 'Dikte suara gagal. Periksa mikrofon lalu coba lagi.';
        isListening.value = false;
    };

    recognition.onend = () => {
        isListening.value = false;
    };

    recognition.start();
}

function stopDictation() {
    recognition?.stop();
    recognition = null;
    isListening.value = false;
}

function toggleDictation() {
    if (isListening.value) {
        stopDictation();

        return;
    }

    startDictation();
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

    recognition?.stop();
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
            <Button
                type="button"
                :variant="isListening ? 'default' : 'ghost'"
                size="icon"
                :title="speechSupported ? (isListening ? 'Hentikan dikte' : 'Dikte suara') : 'Browser tidak mendukung dikte suara'"
                :aria-pressed="isListening"
                :disabled="!speechSupported"
                @click="toggleDictation"
            >
                <MicOff v-if="isListening" />
                <Mic v-else />
            </Button>
            <span v-if="isListening" class="ml-1 text-xs font-medium text-rose-600" aria-live="polite">Mendengarkan…</span>

            <div v-if="props.showTagPicker" class="ml-auto flex items-center gap-1">
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

        <p v-if="speechError" class="border-t border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
            {{ speechError }}
        </p>

        <EditorContent :editor="editor" class="meeting-editor" />
    </div>
</template>
