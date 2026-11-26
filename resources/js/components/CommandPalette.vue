<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { Bell, FileText, Hash, LayoutGrid, ListChecks, Search, Settings, StickyNote, User } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, type Component } from 'vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

interface SearchItem {
    id: number;
    title: string;
    subtitle: string;
    url: string;
}

interface SearchResults {
    meetings: SearchItem[];
    topics: SearchItem[];
    people: SearchItem[];
}

interface ResultItem {
    key: string;
    title: string;
    subtitle?: string;
    url: string;
    icon: Component;
}

const query = ref('');
const loading = ref(false);
const input = ref<HTMLInputElement>();
const activeIndex = ref(0);
const results = ref<SearchResults>({ meetings: [], topics: [], people: [] });

const staticActions: ResultItem[] = [
    { key: 'nav-dashboard', title: 'Dashboard', subtitle: 'Ringkasan hari ini', url: '/dashboard', icon: LayoutGrid },
    { key: 'nav-meetings', title: 'Dokumen', subtitle: 'Semua notulen dan dokumen', url: '/notulen', icon: FileText },
    { key: 'nav-tasks', title: 'Action Item', subtitle: 'Daftar dan board tugas', url: '/tugas', icon: ListChecks },
    { key: 'nav-quick-notes', title: 'Quick Note', subtitle: 'Catatan cepat', url: '/quick-notes', icon: StickyNote },
    { key: 'nav-notifications', title: 'Notifikasi', subtitle: 'Pemberitahuan terbaru', url: '/notifikasi', icon: Bell },
    { key: 'nav-settings', title: 'Pengaturan', subtitle: 'Profil, kata sandi, dan preferensi', url: '/settings/profile', icon: Settings },
];

const filteredActions = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (term === '') {
        return staticActions;
    }

    return staticActions.filter((item) => `${item.title} ${item.subtitle ?? ''}`.toLowerCase().includes(term));
});

const flatItems = computed<ResultItem[]>(() => [
    ...filteredActions.value,
    ...results.value.meetings.map((item) => ({ key: `meeting-${item.id}`, title: item.title, subtitle: item.subtitle, url: item.url, icon: FileText })),
    ...results.value.topics.map((item) => ({ key: `topic-${item.id}`, title: item.title, subtitle: 'Topik', url: item.url, icon: Hash })),
    ...results.value.people.map((item) => ({ key: `person-${item.id}`, title: item.title, subtitle: item.subtitle, url: item.url, icon: User })),
]);

const groupedItems = computed(() => {
    let index = 0;

    return [
        { label: 'Navigasi', items: filteredActions.value },
        {
            label: 'Notulen',
            items: results.value.meetings.map((item) => ({ key: `meeting-${item.id}`, title: item.title, subtitle: item.subtitle, url: item.url, icon: FileText })),
        },
        {
            label: 'Topik',
            items: results.value.topics.map((item) => ({ key: `topic-${item.id}`, title: item.title, subtitle: 'Topik', url: item.url, icon: Hash })),
        },
        {
            label: 'Orang',
            items: results.value.people.map((item) => ({ key: `person-${item.id}`, title: item.title, subtitle: item.subtitle, url: item.url, icon: User })),
        },
    ]
        .filter((group) => group.items.length > 0)
        .map((group) => ({
            label: group.label,
            items: group.items.map((item) => ({ ...item, index: index++ })),
        }));
});

let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let abortController: AbortController | undefined;

async function search() {
    if (query.value.trim().length < 2) {
        results.value = { meetings: [], topics: [], people: [] };
        loading.value = false;
        return;
    }

    abortController?.abort();
    abortController = new AbortController();
    loading.value = true;

    try {
        const response = await fetch(route('global-search', { q: query.value.trim() }), {
            headers: { Accept: 'application/json' },
            signal: abortController.signal,
            credentials: 'same-origin',
        });

        if (response.ok) {
            results.value = (await response.json()) as SearchResults;
        }
    } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
            results.value = { meetings: [], topics: [], people: [] };
        }
    } finally {
        loading.value = false;
    }
}

watch(query, () => {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(search, 220);
});

watch(flatItems, () => {
    activeIndex.value = 0;
});

watch(
    () => props.open,
    async (open) => {
        if (!open) {
            return;
        }

        query.value = '';
        results.value = { meetings: [], topics: [], people: [] };
        activeIndex.value = 0;
        await nextTick();
        input.value?.focus();
    },
);

function choose(item: ResultItem | undefined) {
    if (!item) {
        return;
    }

    emit('update:open', false);
    router.visit(item.url);
}

function onInputKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = flatItems.value.length === 0 ? 0 : (activeIndex.value + 1) % flatItems.value.length;
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = flatItems.value.length === 0 ? 0 : (activeIndex.value - 1 + flatItems.value.length) % flatItems.value.length;
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        choose(flatItems.value[activeIndex.value]);
    }
}

function handleGlobalKeydown(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        emit('update:open', !props.open);
    }
}

onMounted(() => window.addEventListener('keydown', handleGlobalKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    window.clearTimeout(debounceTimer);
    abortController?.abort();
});
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-xl gap-0 overflow-hidden p-0">
            <DialogTitle class="sr-only">Pencarian global</DialogTitle>
            <DialogDescription class="sr-only">Cari notulen, topik, orang, atau navigasi aplikasi.</DialogDescription>

            <div class="flex items-center gap-3 border-b border-border px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                <input
                    ref="input"
                    v-model="query"
                    type="search"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="global-search-results"
                    aria-autocomplete="list"
                    :aria-activedescendant="flatItems[activeIndex] ? `global-search-item-${activeIndex}` : undefined"
                    placeholder="Cari notulen, topik, orang..."
                    class="h-12 min-w-0 flex-1 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
                    @keydown="onInputKeydown"
                />
                <kbd class="hidden rounded border border-border bg-muted px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground sm:inline">Esc</kbd>
            </div>

            <div id="global-search-results" role="listbox" class="max-h-[min(60dvh,28rem)] overflow-y-auto p-2">
                <p v-if="loading" class="px-3 py-6 text-center text-sm text-muted-foreground" aria-live="polite">Mencari…</p>
                <p
                    v-else-if="query.trim().length >= 2 && flatItems.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                    aria-live="polite"
                >
                    Tidak ada hasil untuk “{{ query.trim() }}”.
                </p>

                <section v-for="group in groupedItems" :key="group.label" class="pb-2">
                    <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ group.label }}</p>
                    <button
                        v-for="item in group.items"
                        :id="`global-search-item-${item.index}`"
                        :key="item.key"
                        type="button"
                        role="option"
                        :aria-selected="item.index === activeIndex"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors duration-fast focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                        :class="item.index === activeIndex ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent/60'"
                        @mouseenter="activeIndex = item.index"
                        @click="choose(item)"
                    >
                        <component :is="item.icon" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium">{{ item.title }}</span>
                            <span v-if="item.subtitle" class="block truncate text-xs text-muted-foreground">{{ item.subtitle }}</span>
                        </span>
                    </button>
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-border px-4 py-2 text-[11px] text-muted-foreground">
                <span><kbd class="font-mono">↑↓</kbd> navigasi</span>
                <span><kbd class="font-mono">Enter</kbd> buka</span>
                <span><kbd class="font-mono">Ctrl K</kbd> buka/tutup</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
