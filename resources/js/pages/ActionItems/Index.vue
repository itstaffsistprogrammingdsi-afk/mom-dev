<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { actionStatusLabels, formatDate } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Division, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ListChecks, Search } from 'lucide-vue-next';
import { reactive } from 'vue';

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    items: Paginated<ActionItem>;
    board: Record<string, ActionItem[]> | null;
    view: string;
    filters: Record<string, string | null>;
    stats: { total: number; open: number; overdue: number; done: number };
    users: { id: number; name: string }[];
    divisions: Division[];
    canUpdate: boolean;
}>();

const page = usePage<SharedData>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Action Item', href: '/tugas' }];

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    pic: props.filters.pic ?? '',
    division: props.filters.division ?? '',
    overdue: props.filters.overdue ?? '',
});

function applyFilters() {
    router.get(route('action-items.index'), { ...filters, view: props.view }, { preserveState: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', status: '', pic: '', division: '', overdue: '' });
    applyFilters();
}

function switchView(view: 'list' | 'board') {
    router.get(route('action-items.index'), { ...filters, view }, { preserveState: true, replace: true });
}

const boardColumns = [
    { key: 'new', label: 'Baru' },
    { key: 'in_progress', label: 'Berjalan' },
    { key: 'done', label: 'Selesai' },
    { key: 'cancelled', label: 'Dibatalkan' },
];

function updateStatus(task: ActionItem, status: string) {
    router.patch(route('action-items.status', task.id), { status }, { preserveScroll: true });
}

function canEdit(task: ActionItem) {
    return props.canUpdate || task.pic?.id === page.props.auth.user.id;
}
</script>

<template>
    <Head title="Action Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Action Item" description="Pantau tugas dari seluruh notulen yang dapat Anda akses.">
                <template #actions>
                    <Button :variant="props.view === 'list' ? 'default' : 'outline'" size="sm" @click="switchView('list')">Daftar</Button>
                    <Button :variant="props.view === 'board' ? 'default' : 'outline'" size="sm" @click="switchView('board')">Board</Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Total tugas" :value="props.stats.total" :icon="ListChecks" />
                <StatCard label="Terbuka" :value="props.stats.open" :icon="ListChecks" />
                <StatCard
                    label="Terlambat"
                    :value="props.stats.overdue"
                    :icon="AlertTriangle"
                    :tone="props.stats.overdue > 0 ? 'danger' : 'success'"
                />
                <StatCard label="Selesai" :value="props.stats.done" :icon="CheckCircle2" tone="success" />
            </div>

            <form class="grid gap-2 rounded-lg border bg-card p-3 md:grid-cols-6" @submit.prevent="applyFilters">
                <div class="relative md:col-span-2">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="filters.search" placeholder="Cari tugas…" class="pl-8" />
                </div>
                <select v-model="filters.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua status</option>
                    <option v-for="(label, value) in actionStatusLabels" :key="value" :value="value">{{ label }}</option>
                </select>
                <select v-model="filters.pic" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua PIC</option>
                    <option v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                </select>
                <select v-model="filters.division" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Semua divisi</option>
                    <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                </select>
                <div class="flex gap-2">
                    <Button type="submit" class="flex-1">Filter</Button>
                    <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                </div>
            </form>

            <template v-if="props.view !== 'board'">
            <EmptyState
                v-if="props.items.data.length === 0"
                title="Tidak ada tugas"
                description="Ubah filter atau tambahkan action item dari halaman notulen."
            />

            <div v-else class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="p-3">Tugas</th>
                            <th class="p-3">Notulen</th>
                            <th class="p-3">PIC</th>
                            <th class="p-3">Tenggat</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="task in props.items.data" :key="task.id">
                            <td class="p-3">
                                <p class="font-medium">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ task.division?.name ?? 'Tanpa divisi' }}</p>
                            </td>
                            <td class="p-3">
                                <Link v-if="task.meeting" :href="route('meetings.show', task.meeting.id)" class="text-primary underline">
                                    {{ task.meeting.title }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="p-3">{{ task.pic?.name ?? task.pic_name ?? '—' }}</td>
                            <td class="p-3" :class="{ 'font-medium text-rose-600': task.is_overdue }">{{ formatDate(task.due_date) }}</td>
                            <td class="p-3">
                                <select
                                    v-if="canEdit(task)"
                                    :value="task.status"
                                    class="h-8 rounded-md border bg-background px-2 text-xs"
                                    @change="updateStatus(task, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option value="new">Baru</option>
                                    <option value="in_progress">Berjalan</option>
                                    <option value="done">Selesai</option>
                                    <option value="cancelled">Dibatalkan</option>
                                </select>
                                <StatusBadge v-else kind="action" :value="task.status" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.items.from ?? 0 }}–{{ props.items.to ?? 0 }} dari {{ props.items.total }} tugas
                </p>
                <Pagination :links="props.items.links" />
            </div>
            </template>

            <div v-if="props.view === 'board'" class="grid gap-3 lg:grid-cols-4">
                <div v-for="column in boardColumns" :key="column.key" class="space-y-2">
                    <div class="flex items-center justify-between rounded-md bg-muted px-3 py-2 text-sm font-medium">
                        <span>{{ column.label }}</span>
                        <span class="text-xs text-muted-foreground">{{ props.board?.[column.key]?.length ?? 0 }}</span>
                    </div>
                    <div class="space-y-2">
                        <div v-for="task in props.board?.[column.key] ?? []" :key="task.id" class="rounded-lg border bg-card p-3 text-sm">
                            <p class="font-medium">{{ task.title }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ task.meeting?.title }}</p>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="text-xs">{{ task.pic?.name ?? task.pic_name ?? '—' }}</span>
                                <span class="text-xs" :class="{ 'font-medium text-rose-600': task.is_overdue }">{{ formatDate(task.due_date) }}</span>
                            </div>
                            <select
                                v-if="canEdit(task)"
                                :value="task.status"
                                class="mt-2 h-7 w-full rounded-md border bg-background px-2 text-xs"
                                @change="updateStatus(task, ($event.target as HTMLSelectElement).value)"
                            >
                                <option value="new">Baru</option>
                                <option value="in_progress">Berjalan</option>
                                <option value="done">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>
                        <p v-if="(props.board?.[column.key]?.length ?? 0) === 0" class="rounded-md border border-dashed p-3 text-center text-xs text-muted-foreground">Kosong</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
