<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { documentTypeClasses, documentTypeLabels, formatDateTime, meetingTypeLabels, statusLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { reactive } from 'vue';

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

function applyFilters() {
    router.get(route('meetings.index'), { ...filters }, { preserveState: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', division: '', topic: '', status: '', type: '', document_type: '' });
    applyFilters();
}
</script>

<template>
    <Head title="Notulen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
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

            <form class="grid gap-2 rounded-lg border bg-card p-3 md:grid-cols-7" @submit.prevent="applyFilters">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="filters.search" placeholder="Cari judul atau isi notulen…" class="pl-8" />
                </div>
                <select v-model="filters.division" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua divisi</option>
                    <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                </select>
                <select v-model="filters.topic" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua topik</option>
                    <option v-for="topic in props.topics" :key="topic.id" :value="String(topic.id)">#{{ topic.name }}</option>
                </select>
                <select v-model="filters.document_type" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua jenis dokumen</option>
                    <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
                <select v-model="filters.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua status</option>
                    <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                </select>
                <div class="flex gap-2">
                    <Button type="submit" class="flex-1">Filter</Button>
                    <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                </div>
            </form>

            <EmptyState
                v-if="props.meetings.data.length === 0"
                title="Tidak ada notulen yang cocok"
                description="Coba ubah filter, atau buat notulen baru bila Anda notulis."
            />

            <div v-else class="divide-y rounded-lg border bg-card">
                <Link
                    v-for="meeting in props.meetings.data"
                    :key="meeting.id"
                    :href="route('meetings.show', meeting.id)"
                    class="flex flex-col gap-2 p-4 transition hover:bg-muted/40 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="space-y-1">
                        <p class="font-medium leading-tight">{{ meeting.title }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="documentTypeClasses[meeting.document_type]">
                                {{ documentTypeLabels[meeting.document_type] ?? meeting.document_type }}
                            </span>
                            <span class="text-xs text-muted-foreground">{{ meetingTypeLabels[meeting.meeting_type] }} · {{ formatDateTime(meeting.scheduled_at) }} · {{ meeting.division?.name ?? 'Tanpa divisi' }} · Notulis: {{ meeting.creator?.name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                        <StatusBadge kind="meeting" :value="meeting.status" />
                    </div>
                </Link>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.meetings.from ?? 0 }}–{{ props.meetings.to ?? 0 }} dari {{ props.meetings.total }} notulen
                </p>
                <Pagination :links="props.meetings.links" />
            </div>
        </div>
    </AppLayout>
</template>
