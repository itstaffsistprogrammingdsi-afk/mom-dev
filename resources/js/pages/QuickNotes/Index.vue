<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import QuickNoteMentionChip from '@/components/QuickNoteMentionChip.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/labels';
import type { BreadcrumbItem, Division, QuickNote } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock3, MessageSquare, Plus, Search, StickyNote, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    notes: Paginated<QuickNote>;
    filters: { scope: string; search: string; division: string };
    divisions: Division[];
    stats: { visible: number; mine: number; pending: number; overdue: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Quick Note', href: '/quick-notes' }];

const scope = ref(props.filters.scope ?? 'all');
const search = ref(props.filters.search ?? '');
const division = ref(props.filters.division ?? '');

const tabs = [
    { value: 'all', label: 'Semua' },
    { value: 'mentions', label: 'Untuk saya' },
    { value: 'mine', label: 'Catatan saya' },
];

const activeTab = computed(() => tabs.find((tab) => tab.value === scope.value) ?? tabs[0]);

function applyFilters() {
    router.get(
        route('quick-notes.index'),
        { scope: scope.value, search: search.value || undefined, division: division.value || undefined },
        { preserveState: true, replace: true },
    );
}

function setScope(value: string) {
    scope.value = value;
    applyFilters();
}

function resetSearch() {
    search.value = '';
    applyFilters();
}

function applyDivisionFilter() {
    applyFilters();
}

function pendingMentions(note: QuickNote) {
    return (note.mentions ?? []).filter((mention) => !mention.responded_at).length;
}

function overdueMentions(note: QuickNote) {
    return (note.mentions ?? []).filter((mention) => mention.is_overdue).length;
}
</script>

<template>
    <Head title="Quick Note" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader
                title="Quick Note"
                description="Catat inspirasi atau temuan cepat, lalu tag orang yang perlu merespons dengan batas waktu yang jelas."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="route('quick-notes.create')">
                            <Plus class="size-4" />
                            Catatan baru
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan Quick Note">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Catatan terlihat</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ props.stats.visible }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Catatan saya</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ props.stats.mine }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Menunggu respons saya</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">{{ props.stats.pending }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Lewat batas</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums" :class="props.stats.overdue > 0 ? 'text-destructive-strong' : ''">
                        {{ props.stats.overdue }}
                    </p>
                </div>
            </section>

            <section class="flex flex-col gap-3 rounded-lg border border-border bg-card p-4 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap gap-1" role="tablist" aria-label="Filter Quick Note">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        role="tab"
                        :aria-selected="scope === tab.value"
                        class="inline-flex min-h-9 items-center rounded-md px-3 text-sm font-medium transition-colors duration-fast focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="
                            scope === tab.value
                                ? 'bg-secondary text-secondary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="setScope(tab.value)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="flex w-full flex-col gap-2 sm:flex-row md:w-auto">
                    <div class="w-full sm:w-48">
                        <label for="quick-note-division" class="sr-only">Filter divisi</label>
                        <Select
                            id="quick-note-division"
                            v-model="division"
                            wrapper-class="w-full"
                            aria-label="Filter divisi"
                            @update:model-value="applyDivisionFilter"
                        >
                            <option value="">Semua divisi</option>
                            <option v-for="item in props.divisions" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                        </Select>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <label for="quick-note-search" class="sr-only">Cari catatan</label>
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="quick-note-search"
                            v-model="search"
                            type="search"
                            placeholder="Cari catatan"
                            class="pl-9 pr-9 [&::-webkit-search-cancel-button]:hidden"
                            @keydown.enter.prevent="applyFilters"
                        />
                        <button
                            v-if="search"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            aria-label="Hapus pencarian"
                            @click="resetSearch"
                        >
                            <X class="size-4" />
                        </button>
                    </div>
                </div>
            </section>

            <div class="flex items-end justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">{{ activeTab.label }}</h2>
                    <p class="text-xs text-muted-foreground">{{ props.notes.total }} catatan</p>
                </div>
            </div>

            <EmptyState
                v-if="props.notes.data.length === 0"
                :icon="StickyNote"
                title="Belum ada Quick Note"
                description="Catatan cepat yang Anda buat atau yang menyebut Anda akan muncul di sini."
            >
                <Button as-child class="mt-3">
                    <Link :href="route('quick-notes.create')">
                        <Plus class="size-4" />
                        Buat Quick Note
                    </Link>
                </Button>
            </EmptyState>

            <div v-else class="space-y-3">
                <Link
                    v-for="note in props.notes.data"
                    :key="note.id"
                    :href="route('quick-notes.show', note.id)"
                    class="group block rounded-lg border border-border bg-card p-4 transition-colors duration-fast ease-ui hover:border-primary/30 hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:p-5"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <img
                                v-if="note.photo_url"
                                :src="note.photo_url"
                                alt=""
                                loading="lazy"
                                class="hidden size-16 shrink-0 rounded-md border border-border object-cover sm:block"
                            />
                            <div class="min-w-0 space-y-1.5">
                                <h3 class="line-clamp-2 text-base font-semibold leading-6 text-foreground transition-colors group-hover:text-primary">
                                    {{ note.title }}
                                </h3>
                                <p class="line-clamp-2 text-sm leading-6 text-muted-foreground">
                                    {{ note.content_text || 'Belum ada isi catatan.' }}
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="overdueMentions(note)"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full border border-destructive/30 bg-destructive/10 px-2.5 py-1 text-xs font-medium text-destructive-strong"
                        >
                            {{ overdueMentions(note) }} lewat batas
                        </span>
                    </div>

                    <div v-if="note.mentions?.length" class="mt-3 flex flex-wrap gap-1.5">
                        <QuickNoteMentionChip v-for="mention in note.mentions" :key="mention.id" :mention="mention" />
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-border pt-3 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5">
                            <Clock3 class="size-3.5" aria-hidden="true" />
                            {{ formatDateTime(note.created_at) }}
                        </span>
                        <span>oleh {{ note.creator?.name ?? 'Pengguna' }}</span>
                        <span>{{ note.division?.name ?? 'Tanpa divisi' }}</span>
                        <span v-if="pendingMentions(note)" class="inline-flex items-center gap-1.5">
                            <MessageSquare class="size-3.5" aria-hidden="true" />
                            {{ pendingMentions(note) }} menunggu respons
                        </span>
                    </div>
                </Link>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.notes.from ?? 0 }}–{{ props.notes.to ?? 0 }} dari {{ props.notes.total }} catatan
                </p>
                <Pagination :links="props.notes.links" />
            </div>
        </div>
    </AppLayout>
</template>
