<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import { documentTypeClasses, documentTypeLabels, formatDateTime, meetingTypeLabels, statusLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, RotateCcw, Search, X } from 'lucide-vue-next';
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

const filters = reactive({
    search: props.filters.search ?? '',
    division: props.filters.division ?? '',
    topic: props.filters.topic ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    document_type: props.filters.document_type ?? '',
});

const isFiltering = ref(false);

const activeFilterCount = computed(() => Object.values(filters).filter((value) => value !== '').length);
const hasActiveFilters = computed(() => activeFilterCount.value > 0);

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

function applyFilters() {
    isFiltering.value = true;

    router.get(
        route('meetings.index'),
        { ...filters },
        {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
}

function resetFilters() {
    Object.assign(filters, { search: '', division: '', topic: '', status: '', type: '', document_type: '' });
    applyFilters();
}
</script>

<template>
    <Head title="Notulen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader title="Notulen & Dokumen" description="Seluruh notulen, proposal, laporan, dan brosur yang dapat Anda akses.">
                <template #actions>
                    <Button v-if="props.canCreate" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" />
                            Buat dokumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <section aria-label="Filter dokumen" class="rounded-lg border border-border bg-card p-4 shadow-card md:p-5">
                <form @submit.prevent="applyFilters">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-2 xl:grid-cols-12">
                        <div class="relative col-span-2 md:col-span-2 xl:col-span-2">
                            <label for="document-search" class="sr-only">Cari dokumen</label>
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                id="document-search"
                                v-model="filters.search"
                                type="search"
                                placeholder="Cari dokumen"
                                class="pl-9 pr-9 [&::-webkit-search-cancel-button]:hidden"
                            />
                            <button
                                v-if="filters.search"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                aria-label="Hapus kata kunci"
                                @click="filters.search = ''"
                            >
                                <X class="size-4" />
                            </button>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="filter-division" class="sr-only">Divisi</label>
                            <Select id="filter-division" v-model="filters.division">
                                <option value="">Semua divisi</option>
                                <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">
                                    {{ division.name }}
                                </option>
                            </Select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="filter-topic" class="sr-only">Topik</label>
                            <Select id="filter-topic" v-model="filters.topic">
                                <option value="">Semua topik</option>
                                <option v-for="topic in props.topics" :key="topic.id" :value="String(topic.id)">#{{ topic.name }}</option>
                            </Select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="filter-document-type" class="sr-only">Jenis dokumen</label>
                            <Select id="filter-document-type" v-model="filters.document_type">
                                <option value="">Semua jenis</option>
                                <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </Select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="filter-meeting-type" class="sr-only">Jenis rapat</label>
                            <Select id="filter-meeting-type" v-model="filters.type">
                                <option value="">Semua rapat</option>
                                <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                            </Select>
                        </div>

                        <div class="xl:col-span-2">
                            <label for="filter-status" class="sr-only">Status</label>
                            <Select id="filter-status" v-model="filters.status">
                                <option value="">Semua status</option>
                                <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                            </Select>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-muted-foreground" aria-live="polite">
                            {{ hasActiveFilters ? `${activeFilterCount} filter aktif` : 'Belum ada filter aktif' }}
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Button type="button" variant="ghost" :disabled="!hasActiveFilters" @click="resetFilters">
                                <RotateCcw class="size-4" />
                                Reset filter
                            </Button>
                            <Button type="submit" :loading="isFiltering">
                                <Search class="size-4" />
                                Terapkan filter
                            </Button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="space-y-3" aria-labelledby="document-list-heading">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 id="document-list-heading" class="text-sm font-semibold text-foreground">Daftar dokumen</h2>
                        <p class="text-xs text-muted-foreground">{{ props.meetings.total }} dokumen{{ hasActiveFilters ? ' sesuai filter' : '' }}</p>
                    </div>
                </div>

                <div
                    v-if="isFiltering"
                    class="divide-y divide-border overflow-hidden rounded-lg border border-border bg-card shadow-card"
                    aria-hidden="true"
                >
                    <div v-for="index in 4" :key="index" class="flex items-center gap-4 p-4 md:p-5">
                        <Skeleton class="hidden size-12 shrink-0 rounded-md sm:block" />
                        <div class="flex-1 space-y-2">
                            <Skeleton class="h-4 w-1/3" />
                            <Skeleton class="h-3 w-2/3" />
                        </div>
                        <Skeleton class="h-6 w-20 rounded-full" />
                    </div>
                </div>

                <div v-else-if="props.meetings.data.length === 0">
                    <EmptyState
                        title="Tidak ada dokumen yang cocok"
                        description="Coba kata kunci atau filter lain, atau buat dokumen baru bila Anda notulis."
                    >
                        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                            <Button v-if="hasActiveFilters" variant="outline" @click="resetFilters">
                                <RotateCcw class="size-4" />
                                Reset filter
                            </Button>
                            <Button v-if="props.canCreate" as-child>
                                <Link :href="route('meetings.create')">
                                    <Plus class="size-4" />
                                    Buat dokumen
                                </Link>
                            </Button>
                        </div>
                    </EmptyState>
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-border bg-card shadow-card" :aria-busy="isFiltering">
                    <Link
                        v-for="meeting in props.meetings.data"
                        :key="meeting.id"
                        :href="route('meetings.show', meeting.id)"
                        class="group flex flex-col gap-3 border-b border-border p-4 transition-colors duration-fast ease-ui last:border-b-0 hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring sm:flex-row sm:items-center sm:gap-4 md:p-5"
                    >
                        <div
                            class="hidden w-12 shrink-0 flex-col items-center justify-center rounded-md border border-border bg-muted/40 py-2 sm:flex"
                        >
                            <span class="text-[10px] font-medium text-muted-foreground">{{ dateParts(meeting.scheduled_at).month }}</span>
                            <span class="text-lg font-semibold leading-6 text-foreground">{{ dateParts(meeting.scheduled_at).day }}</span>
                        </div>

                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold leading-5"
                                    :class="documentTypeClasses[meeting.document_type]"
                                >
                                    {{ documentTypeLabels[meeting.document_type] ?? meeting.document_type }}
                                </span>
                                <h3
                                    class="line-clamp-2 min-w-0 text-[15px] font-semibold leading-6 text-foreground transition-colors duration-fast group-hover:text-primary"
                                >
                                    {{ meeting.title }}
                                </h3>
                            </div>
                            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs leading-5 text-muted-foreground">
                                <span>{{ meetingTypeLabels[meeting.meeting_type] ?? meeting.meeting_type }}</span>
                                <span aria-hidden="true" class="text-border">·</span>
                                <span>{{ formatDateTime(meeting.scheduled_at) }}</span>
                                <span aria-hidden="true" class="text-border">·</span>
                                <span>{{ meeting.division?.name ?? 'Tanpa divisi' }}</span>
                                <span aria-hidden="true" class="text-border">·</span>
                                <span>Notulis {{ meeting.creator?.name ?? '-' }}</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end">
                            <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                            <StatusBadge kind="meeting" :value="meeting.status" />
                        </div>
                    </Link>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.meetings.from ?? 0 }}–{{ props.meetings.to ?? 0 }} dari {{ props.meetings.total }} dokumen
                </p>
                <Pagination :links="props.meetings.links" />
            </div>
        </div>
    </AppLayout>
</template>
