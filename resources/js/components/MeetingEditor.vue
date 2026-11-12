<script setup lang="ts">
import { SlashCommand } from '@/components/editor/slashCommand';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
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
import {
    AlignCenterVertical,
    AtSign,
    Bold,
    Heading2,
    Italic,
    Link2,
    List,
    ListOrdered,
    ListTodo,
    Maximize2,
    Mic,
    MicOff,
    Minimize2,
    Redo2,
    Table2,
    Undo2,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as Y from 'yjs';

interface Props {
    modelValue?: JSONContent | Record<string, unknown> | null;
    editable?: boolean;
    users?: (Pick<User, 'id' | 'name'> & { division_id?: number | null })[];
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
    initials: string;
}

function personAndDivisionItems(query: string): MentionItem[] {
    const normalized = query.trim().toLowerCase();

    const people = props.users
        .filter((user) => user.name.toLowerCase().includes(normalized))
        .map((user) => ({
            id: user.id,
            label: user.name,
            type: 'person' as const,
            hint: props.divisions.find((division) => division.id === user.division_id)?.name ?? 'Orang',
            initials: getInitials(user.name),
        }));

    const divisions = props.divisions
        .filter((division) => division.name.toLowerCase().includes(normalized))
        .map((division) => ({ id: division.id, label: division.name, type: 'division' as const, hint: 'Divisi', initials: '' }));

    return [...people, ...divisions];
}

function topicItems(query: string): MentionItem[] {
    const normalized = query.trim().toLowerCase();

    return props.topics
        .filter((topic) => topic.name.toLowerCase().includes(normalized))
        .map((topic) => ({ id: topic.id, label: topic.name, type: 'topic' as const, hint: 'Topik', initials: '' }));
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
                              .map((item, index) => {
                                  const avatar =
                                      item.type === 'person'
                                          ? `<span class="mention-suggestion-avatar" aria-hidden="true">${escapeHtml(item.initials)}</span>`
                                          : `<span class="mention-suggestion-type" data-type="${item.type}" aria-hidden="true">${char}</span>`;

                                  return `
                                    <div role="option" id="mention-option-${index}" aria-selected="${index === selected}" data-selected="${index === selected}" data-index="${index}" data-type="${item.type}" class="mention-suggestion-item">
                                        ${avatar}
                                        <span class="mention-suggestion-copy">
                                            <span class="mention-suggestion-label">${escapeHtml(item.label)}</span>
                                            <span class="mention-suggestion-hint">${escapeHtml(item.hint)}</span>
                                        </span>
                                    </div>
                                `;
                              })
                              .join('');

                popup.setAttribute('aria-activedescendant', items.length > 0 ? `mention-option-${selected}` : '');

                popup.querySelectorAll<HTMLElement>('[data-index]').forEach((option) => {
                    option.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        select(items[Number(option.dataset.index)]);
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

                const popupWidth = 320;
                const left = Math.min(bounds.left + window.scrollX, window.scrollX + window.innerWidth - popupWidth - 16);
                const top = Math.min(bounds.bottom + window.scrollY + 8, window.scrollY + window.innerHeight - 300);

                popup.style.left = `${Math.max(window.scrollX + 12, left)}px`;
                popup.style.top = `${Math.max(window.scrollY + 12, top)}px`;
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

                    if (props.event.key === 'Home') {
                        selected = 0;
                        paint();

                        return true;
                    }

                    if (props.event.key === 'End') {
                        selected = Math.max(items.length - 1, 0);
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

const focusMode = ref(false);
const typewriterMode = ref(false);
const editorScroll = ref<HTMLElement | null>(null);

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
        SlashCommand,
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
                      user: { name: props.collabUser ?? 'Pengguna', color: '#2456C7' },
                  }),
              ]
            : []),
    ],
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.getJSON());
        updateWordCount();
    },
    onSelectionUpdate: () => {
        centerCaret();
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

function insertTag(value?: string | number) {
    const key = String(value ?? tagSelection.value);
    const selected = tagOptions.value.find((option) => option.key === key);

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

const linkOpen = ref(false);
const linkUrl = ref('');

function openLinkDialog() {
    if (!editor.value) {
        return;
    }

    linkUrl.value = (editor.value.getAttributes('link').href as string | undefined) ?? '';
    linkOpen.value = true;
}

function applyLink() {
    if (!editor.value) {
        return;
    }

    const url = linkUrl.value.trim();

    if (!url) {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    }

    linkOpen.value = false;
}

function removeLink() {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    linkOpen.value = false;
}

const wordCount = ref(0);
const readingTime = computed(() => Math.max(1, Math.ceil(wordCount.value / 200)));

function updateWordCount() {
    const text = editor.value?.getText() ?? '';
    const trimmed = text.trim();
    wordCount.value = trimmed ? trimmed.split(/\s+/).length : 0;
}

function centerCaret() {
    if (!typewriterMode.value || !editor.value) {
        return;
    }

    const view = editor.value.view;
    const coords = view.coordsAtPos(view.state.selection.head);
    const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    const container = focusMode.value ? editorScroll.value : null;

    if (container) {
        const rect = container.getBoundingClientRect();
        container.scrollBy({ top: coords.top - rect.top - rect.height / 2, behavior });
    } else {
        window.scrollBy({ top: coords.top - window.innerHeight / 2, behavior });
    }
}

function toggleFocusMode() {
    focusMode.value = !focusMode.value;
}

function handleEscape(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'f') {
        event.preventDefault();
        focusMode.value = !focusMode.value;

        return;
    }

    if (event.key === 'Escape' && focusMode.value && !document.querySelector('.mention-suggestion')) {
        focusMode.value = false;
    }
}

watch(focusMode, (value) => {
    document.body.classList.toggle('overflow-hidden', value);
});

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

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    window.setTimeout(updateWordCount, 200);
});

onBeforeUnmount(() => {
    if (seedTimer) {
        clearTimeout(seedTimer);
    }

    document.removeEventListener('keydown', handleEscape);
    document.body.classList.remove('overflow-hidden');
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
    <div
        :data-readonly="!editable"
        :class="
            focusMode
                ? 'fixed inset-0 z-[70] flex flex-col bg-background'
                : 'group rounded-lg border border-border/80 bg-card shadow-soft-sm transition-[border-color,box-shadow] duration-base ease-out-soft focus-within:border-primary/40 focus-within:shadow-soft-md'
        "
    >
        <div
            v-if="editor && editable"
            role="toolbar"
            aria-label="Format notulensi"
            :class="
                focusMode
                    ? 'sticky top-0 z-20 flex flex-nowrap items-center gap-0.5 overflow-x-auto border-b border-border/70 bg-surface px-3 py-2 [&>button]:shrink-0'
                    : 'sticky top-14 z-20 flex flex-nowrap items-center gap-0.5 overflow-x-auto rounded-t-lg border-b border-border/70 bg-surface px-2 py-2 shadow-sm md:top-16 md:flex-wrap md:overflow-visible [&>button]:shrink-0'
            "
        >
            <div class="flex items-center gap-0.5">
                <Button
                    type="button"
                    :variant="editor.isActive('bold') ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Tebal"
                    :aria-pressed="editor.isActive('bold')"
                    @click="editor.chain().focus().toggleBold().run()"
                >
                    <Bold />
                </Button>
                <Button
                    type="button"
                    :variant="editor.isActive('italic') ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Miring"
                    :aria-pressed="editor.isActive('italic')"
                    @click="editor.chain().focus().toggleItalic().run()"
                >
                    <Italic />
                </Button>
                <Button
                    type="button"
                    :variant="editor.isActive('heading', { level: 2 }) ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Judul bagian"
                    :aria-pressed="editor.isActive('heading', { level: 2 })"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                >
                    <Heading2 />
                </Button>
            </div>

            <span class="mx-1 h-5 w-px bg-border" aria-hidden="true" />

            <div class="flex items-center gap-0.5">
                <Button
                    type="button"
                    :variant="editor.isActive('bulletList') ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Daftar butir"
                    :aria-pressed="editor.isActive('bulletList')"
                    @click="editor.chain().focus().toggleBulletList().run()"
                >
                    <List />
                </Button>
                <Button
                    type="button"
                    :variant="editor.isActive('orderedList') ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Daftar bernomor"
                    :aria-pressed="editor.isActive('orderedList')"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                >
                    <ListOrdered />
                </Button>
                <Button
                    type="button"
                    :variant="editor.isActive('taskList') ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Daftar tugas"
                    :aria-pressed="editor.isActive('taskList')"
                    @click="editor.chain().focus().toggleTaskList().run()"
                >
                    <ListTodo />
                </Button>
            </div>

            <span class="mx-1 h-5 w-px bg-border" aria-hidden="true" />

            <div class="flex items-center gap-0.5">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    aria-label="Sisipkan tabel"
                    @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
                >
                    <Table2 />
                </Button>
                <Button type="button" variant="ghost" size="icon" class="size-8" aria-label="Sisipkan tautan" @click="openLinkDialog">
                    <Link2 />
                </Button>
            </div>

            <span class="mx-1 h-5 w-px bg-border" aria-hidden="true" />

            <div class="flex items-center gap-0.5">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    aria-label="Urungkan"
                    :disabled="!editor.can().undo()"
                    @click="editor.chain().focus().undo().run()"
                >
                    <Undo2 />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    aria-label="Ulangi"
                    :disabled="!editor.can().redo()"
                    @click="editor.chain().focus().redo().run()"
                >
                    <Redo2 />
                </Button>
            </div>

            <span class="mx-1 h-5 w-px bg-border" aria-hidden="true" />

            <Button
                type="button"
                :variant="isListening ? 'destructive' : 'ghost'"
                size="icon"
                class="size-8"
                :aria-label="speechSupported ? (isListening ? 'Hentikan dikte' : 'Dikte suara') : 'Browser tidak mendukung dikte suara'"
                :aria-pressed="isListening"
                :disabled="!speechSupported"
                @click="toggleDictation"
            >
                <MicOff v-if="isListening" />
                <Mic v-else />
            </Button>

            <span
                v-if="isListening"
                class="ml-1 inline-flex items-center gap-1.5 rounded-full bg-destructive-soft px-2 py-1 text-xs font-medium text-destructive-strong"
            >
                <span class="size-1.5 animate-pulse rounded-full bg-destructive" aria-hidden="true" />
                Mendengarkan
            </span>

            <div class="ml-auto flex items-center gap-1">
                <template v-if="props.showTagPicker && !focusMode">
                    <AtSign class="size-4 text-subtle-foreground" aria-hidden="true" />
                    <Select
                        v-model="tagSelection"
                        wrapper-class="w-48 shrink-0"
                        class="h-8 text-xs"
                        aria-label="Sisipkan tag"
                        @update:model-value="insertTag"
                    >
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
                    </Select>
                </template>

                <Button
                    type="button"
                    :variant="typewriterMode ? 'secondary' : 'ghost'"
                    size="icon"
                    class="size-8"
                    aria-label="Typewriter scrolling"
                    :aria-pressed="typewriterMode"
                    @click="typewriterMode = !typewriterMode"
                >
                    <AlignCenterVertical />
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :aria-label="focusMode ? 'Keluar mode fokus (Ctrl+Shift+F)' : 'Mode fokus (Ctrl+Shift+F)'"
                    :aria-pressed="focusMode"
                    @click="toggleFocusMode"
                >
                    <Minimize2 v-if="focusMode" />
                    <Maximize2 v-else />
                </Button>
            </div>
        </div>

        <p
            v-if="speechError"
            class="mx-3 mt-3 flex items-start gap-2 rounded-md border border-destructive/20 bg-destructive-soft px-3 py-2 text-xs leading-relaxed text-destructive-strong"
            role="alert"
        >
            {{ speechError }}
        </p>

        <div ref="editorScroll" :class="focusMode ? 'min-h-0 flex-1 overflow-y-auto' : ''">
            <EditorContent :editor="editor" class="meeting-editor" />
        </div>

        <div
            v-if="editor && editable"
            class="flex flex-wrap items-center justify-between gap-2 border-t border-border/60 px-4 py-2 text-xs text-muted-foreground"
        >
            <span class="tabular-nums">{{ wordCount }} kata</span>
            <span class="tabular-nums">± {{ readingTime }} menit baca</span>
            <span v-if="focusMode" class="hidden sm:inline">Esc untuk keluar dari mode fokus</span>
        </div>

        <Dialog v-model:open="linkOpen">
            <DialogContent class="z-[80] sm:max-w-md">
                <form class="space-y-4" @submit.prevent="applyLink">
                    <DialogHeader>
                        <DialogTitle>Tautan</DialogTitle>
                        <DialogDescription>Masukkan alamat tautan untuk teks yang dipilih.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-1.5">
                        <Label for="editor-link-url">Alamat tautan</Label>
                        <Input id="editor-link-url" v-model="linkUrl" placeholder="https://…" autofocus />
                    </div>

                    <DialogFooter>
                        <Button v-if="editor?.isActive('link')" type="button" variant="ghost" @click="removeLink">Hapus tautan</Button>
                        <Button type="button" variant="outline" @click="linkOpen = false">Batal</Button>
                        <Button type="submit">Terapkan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
