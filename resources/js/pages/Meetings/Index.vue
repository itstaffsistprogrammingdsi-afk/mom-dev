<script setup lang="ts">
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime, meetingTypeLabels, statusLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bookmark, FileText, Plus, RotateCcw, Search, SlidersHorizontal, Trash2, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    meetings: Paginated<Meeting>;
    filters: Record<string, string | null>;
    divisions: Division[];
    topics: { id: number; name: string }[];
    documentTypes: { value: string; label: string }[];
    canCreate: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dokumen', href: '/notulen' }];

function dateParts(value?: string | null): { day: string; month: string } {
    if (!value) {
        return { day: '--', month: '-' };
    }

    const date = new Date(value);

    return {
        day: new Intl.DateTimeFormat('id-ID', { day: '2-digit' }).format(date),
        month: new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(date),
    };
}

const filters = reactive({
    search: props.filters.search ?? '',
    division: props.filters.division ?? '',
    topic: props.filters.topic ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    document_type: props.filters.document_type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const isLoading = ref(false);
const toast = useToast();

function toDateInputValue(date: Date): string {
    const copy = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return copy.toISOString().slice(0, 10);
}

function setDateRange(days: number) {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - days);
    filters.from = toDateInputValue(from);
    filters.to = toDateInputValue(to);
    applyFilters();
}

function clearDateRange() {
    filters.from = '';
    filters.to = '';
    applyFilters();
}

interface FilterPreset {
    id: string;
    name: string;
    filters: Record<string, string>;
}

const presetStorageKey = 'mom.meeting-filter-presets';

function loadPresets(): FilterPreset[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(presetStorageKey);

        return raw ? (JSON.parse(raw) as FilterPreset[]) : [];
    } catch {
        return [];
    }
}

const presets = ref<FilterPreset[]>(loadPresets());
const presetDialogOpen = ref(false);
const presetName = ref('');

function persistPresets() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(presetStorageKey, JSON.stringify(presets.value));
}

function savePreset() {
    const name = presetName.value.trim();

    if (!name) {
        return;
    }

    presets.value = [...presets.value, { id: `${Date.now()}`, name, filters: { ...filters } }];
    persistPresets();
    presetName.value = '';
    toast.success('Preset disimpan', name);
}

function applyPreset(preset: FilterPreset) {
    Object.assign(filters, { search: '', division: '', topic: '', status: '', type: '', document_type: '', from: '', to: '' }, preset.filters);
    presetDialogOpen.value = false;
    applyFilters();
}

function deletePreset(id: string) {
    const removed = presets.value.find((preset) => preset.id === id);
    presets.value = presets.value.filter((preset) => preset.id !== id);
    persistPresets();

    if (!removed) {
        return;
    }

    toast.push({
        title: 'Preset dihapus',
        description: removed.name,
        variant: 'neutral',
        actionLabel: 'Urungkan',
        onAction: () => {
            presets.value = [...presets.value, removed];
            persistPresets();
        },
    });
}

type FilterKey = keyof typeof filters;

const activeFilterChips = computed<{ key: FilterKey; label: string }[]>(() => {
    const chips: { key: FilterKey; label: string }[] = [];

    if (filters.search) {
        chips.push({ key: 'search', label: `Cari: ${filters.search}` });
    }

    if (filters.division) {
        const division = props.divisions.find((item) => String(item.id) === filters.division);
        chips.push({ key: 'division', label: `Divisi: ${division?.name ?? filters.division}` });
    }

    if (filters.topic) {
        const topic = props.topics.find((item) => String(item.id) === filters.topic);
        chips.push({ key: 'topic', label: `Topik: #${topic?.name ?? filters.topic}` });
    }

    if (filters.document_type) {
        const type = props.documentTypes.find((item) => item.value === filters.document_type);
        chips.push({ key: 'document_type', label: `Jenis: ${type?.label ?? filters.document_type}` });
    }

    if (filters.type) {
        chips.push({ key: 'type', label: `Rapat: ${meetingTypeLabels[filters.type] ?? filters.type}` });
    }

    if (filters.status) {
        chips.push({ key: 'status', label: `Status: ${statusLabels[filters.status] ?? filters.status}` });
    }

    if (filters.from) {
        chips.push({ key: 'from', label: `Dari: ${filters.from}` });
    }

    if (filters.to) {
        chips.push({ key: 'to', label: `Sampai: ${filters.to}` });
    }

    return chips;
});

function clearFilter(key: FilterKey) {
    filters[key] = '';
    applyFilters();
}

function applyFilters() {
    isLoading.value = true;
    router.get(route('meetings.index'), { ...filters }, { preserveState: true, replace: true, onFinish: () => (isLoading.value = false) });
}

function resetFilters() {
    const previous = { ...filters };

    Object.assign(filters, {
        search: '',
        division: '',
        topic: '',
        status: '',
        type: '',
        document_type: '',
        from: '',
        to: '',
    });

    toast.push({
        title: 'Filter direset',
        description: 'Filter sebelumnya masih bisa dikembalikan.',
        variant: 'info',
        actionLabel: 'Urungkan',
        onAction: () => {
            Object.assign(filters, previous);
            applyFilters();
        },
    });

    applyFilters();
}
</script>

<template>
    <Head title="Dokumen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader title="Notulen & Dokumen" description="Seluruh notulen, proposal, laporan, dan brosur yang dapat Anda akses.">
                <template #actions>
                    <Button v-if="props.canCreate" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Buat dokumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="rounded-lg border border-border bg-card p-4 md:p-5" @submit.prevent="applyFilters">
                <div class="mb-4 flex items-start gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
                        <SlidersHorizontal class="size-4" aria-hidden="true" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-foreground">Cari dan filter dokumen</p>
                        <p class="text-xs leading-relaxed text-muted-foreground">Kombinasikan kata kunci, divisi, topik, status, dan tanggal.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" class="ml-auto shrink-0" @click="presetDialogOpen = true">
                        <Bookmark class="size-3.5" aria-hidden="true" />
                        Preset
                    </Button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="meeting-search">Cari</Label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                id="meeting-search"
                                v-model="filters.search"
                                type="search"
                                placeholder="Judul atau isi dokumen"
                                class="pl-9 pr-9 [&::-webkit-search-cancel-button]:hidden"
                            />
                            <button
                                v-if="filters.search"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                aria-label="Hapus kata kunci"
                                @click="filters.search = ''"
                            >
                                <X class="size-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-division">Divisi</Label>
                        <Select id="filter-division" v-model="filters.division">
                            <option value="">Semua divisi</option>
                            <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-topic">Topik</Label>
                        <Select id="filter-topic" v-model="filters.topic">
                            <option value="">Semua topik</option>
                            <option v-for="topic in props.topics" :key="topic.id" :value="String(topic.id)">#{{ topic.name }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-document-type">Jenis dokumen</Label>
                        <Select id="filter-document-type" v-model="filters.document_type">
                            <option value="">Semua jenis</option>
                            <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-meeting-type">Jenis rapat</Label>
                        <Select id="filter-meeting-type" v-model="filters.type">
                            <option value="">Semua jenis rapat</option>
                            <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="filter-status">Status</Label>
                        <Select id="filter-status" v-model="filters.status">
                            <option value="">Semua status</option>
                            <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <Label>Rentang tanggal</Label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <Input v-model="filters.from" type="date" aria-label="Dari tanggal" />
                            <Input v-model="filters.to" type="date" aria-label="Sampai tanggal" />
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring max-sm:min-h-11"
                                @click="setDateRange(6)"
                            >
                                7 hari terakhir
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring max-sm:min-h-11"
                                @click="setDateRange(29)"
                            >
                                30 hari terakhir
                            </button>
                            <button
                                v-if="filters.from || filters.to"
                                type="button"
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium text-primary transition-colors duration-fast hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring max-sm:min-h-11"
                                @click="clearDateRange"
                            >
                                Hapus tanggal
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border/60 pt-4">
                    <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    <Button type="submit">
                        <Search class="size-4" aria-hidden="true" />
                        Terapkan filter
                    </Button>
                </div>
            </form>

            <div v-if="activeFilterChips.length" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-medium text-subtle-foreground">Filter aktif</span>
                <button
                    v-for="chip in activeFilterChips"
                    :key="chip.key"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full border border-border bg-surface px-2.5 py-1 text-xs text-secondary-foreground transition-colors duration-fast ease-out-soft hover:border-primary/30 hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                    :aria-label="`Hapus filter ${chip.label}`"
                    @click="clearFilter(chip.key)"
                >
                    {{ chip.label }}
                    <X class="size-3" aria-hidden="true" />
                </button>
                <Button variant="ghost" size="sm" @click="resetFilters">Hapus semua</Button>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">Daftar dokumen</h2>
                    <p class="text-xs text-muted-foreground">
                        {{ props.meetings.total }} dokumen{{ activeFilterChips.length ? ' sesuai filter' : '' }}
                    </p>
                </div>
            </div>

            <div v-if="isLoading" class="overflow-hidden rounded-lg border border-border bg-card" aria-busy="true" aria-label="Memuat dokumen">
                <div v-for="index in 3" :key="index" class="flex items-center gap-4 border-b border-border/60 p-4 last:border-b-0">
                    <Skeleton class="size-10 shrink-0 rounded-md" />
                    <div class="min-w-0 flex-1 space-y-2">
                        <Skeleton class="h-4 w-2/3" />
                        <Skeleton class="h-3 w-1/2" />
                    </div>
                    <Skeleton class="h-5 w-16 rounded-full" />
                </div>
            </div>

            <EmptyState
                v-else-if="props.meetings.data.length === 0"
                :icon="FileText"
                title="Tidak ada dokumen yang cocok"
                description="Coba longgarkan filter, atau buat dokumen baru bila Anda memiliki akses notulis."
            >
                <div class="flex flex-wrap justify-center gap-2">
                    <Button variant="outline" @click="resetFilters">
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Reset filter
                    </Button>
                    <Button v-if="props.canCreate" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Buat dokumen
                        </Link>
                    </Button>
                </div>
            </EmptyState>

            <div v-else class="overflow-hidden rounded-lg border border-border bg-card" :aria-busy="isLoading">
                <Link
                    v-for="meeting in props.meetings.data"
                    :key="meeting.id"
                    :href="route('meetings.show', meeting.id)"
                    class="group flex flex-col gap-3 border-b border-border p-4 transition-colors duration-fast ease-ui last:border-b-0 hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring sm:flex-row sm:items-center sm:gap-4 md:p-5"
                >
                    <div class="hidden w-12 shrink-0 flex-col items-center justify-center rounded-md border border-border bg-muted/40 py-2 sm:flex">
                        <span class="text-[11px] font-medium text-muted-foreground">{{ dateParts(meeting.scheduled_at).month }}</span>
                        <span class="text-lg font-semibold leading-6 text-foreground">{{ dateParts(meeting.scheduled_at).day }}</span>
                    </div>

                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p class="truncate font-medium leading-snug text-foreground transition-colors group-hover:text-primary">
                            {{ meeting.title }}
                        </p>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs leading-relaxed text-muted-foreground">
                            <DocumentTypeBadge :type="meeting.document_type" />
                            <span>{{ meetingTypeLabels[meeting.meeting_type] }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ formatDateTime(meeting.scheduled_at) }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ meeting.division?.name ?? 'Tanpa divisi' }}</span>
                            <span aria-hidden="true">·</span>
                            <span>Notulis: {{ meeting.creator?.name ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-1.5">
                        <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                        <StatusBadge kind="meeting" :value="meeting.status" />
                    </div>
                </Link>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.meetings.from ?? 0 }}–{{ props.meetings.to ?? 0 }} dari {{ props.meetings.total }} dokumen
                </p>
                <Pagination :links="props.meetings.links" />
            </div>
        </div>

        <Dialog v-model:open="presetDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Preset filter</DialogTitle>
                    <DialogDescription>Simpan kombinasi filter untuk dipakai lagi tanpa mengatur ulang.</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="preset-name">Nama preset</Label>
                        <div class="flex gap-2">
                            <Input
                                id="preset-name"
                                v-model="presetName"
                                placeholder="Contoh: Rapat IT bulan ini"
                                @keydown.enter.prevent="savePreset"
                            />
                            <Button type="button" :disabled="!presetName.trim()" @click="savePreset">Simpan</Button>
                        </div>
                    </div>

                    <div v-if="presets.length" class="space-y-2">
                        <p class="text-xs font-medium text-subtle-foreground">Preset tersimpan</p>
                        <div
                            v-for="preset in presets"
                            :key="preset.id"
                            class="flex items-center justify-between gap-2 rounded-md border border-border/70 p-2"
                        >
                            <button
                                type="button"
                                class="min-w-0 flex-1 truncate text-left text-sm font-medium hover:text-primary"
                                @click="applyPreset(preset)"
                            >
                                {{ preset.name }}
                            </button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8"
                                :aria-label="`Hapus preset ${preset.name}`"
                                @click="deletePreset(preset.id)"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </Button>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">Belum ada preset. Simpan filter saat ini untuk mulai.</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="presetDialogOpen = false">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
