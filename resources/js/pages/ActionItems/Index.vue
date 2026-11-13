<script setup lang="ts">
import ActionItemFeedbackDialog from '@/components/ActionItemFeedbackDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { actionStatusLabels, formatDate, formatDateTime } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Division, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ListChecks, MessageSquare, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

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
    owner: props.filters.owner ?? '',
});

const feedbackTaskId = ref<number | null>(null);
const feedbackPendingStatus = ref<string | null>(null);
const selectedFeedbackTask = computed(() => {
    const direct = props.items.data.find((task) => task.id === feedbackTaskId.value);

    if (direct) {
        return direct;
    }

    for (const tasks of Object.values(props.board ?? {})) {
        const found = tasks.find((task) => task.id === feedbackTaskId.value);

        if (found) {
            return found;
        }
    }

    return null;
});

function openFeedback(task: ActionItem) {
    feedbackPendingStatus.value = null;
    feedbackTaskId.value = task.id;
}

function canRespond(task: ActionItem) {
    return props.canUpdate || task.pic?.id === page.props.auth.user.id;
}

const selectedIds = ref<number[]>([]);
const bulkStatus = ref('');
const bulkProcessing = ref(false);
const toast = useToast();

const allSelected = computed(() => props.items.data.length > 0 && selectedIds.value.length === props.items.data.length);

function toggleAll(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedIds.value = checked ? props.items.data.map((task) => task.id) : [];
}

async function applyBulkStatus() {
    if (!bulkStatus.value || selectedIds.value.length === 0) {
        return;
    }

    bulkProcessing.value = true;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const ids = [...selectedIds.value];

    try {
        for (const id of ids) {
            const response = await fetch(route('action-items.status', id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: bulkStatus.value }),
            });

            if (!response.ok) {
                throw new Error('Bulk update failed');
            }
        }

        toast.success('Status diperbarui', `${ids.length} tugas berhasil diubah.`);
        selectedIds.value = [];
        bulkStatus.value = '';
        router.reload();
    } catch {
        toast.error('Gagal memperbarui', 'Sebagian tugas mungkin belum berubah. Muat ulang lalu coba lagi.');
    } finally {
        bulkProcessing.value = false;
    }
}

function applyFilters() {
    selectedIds.value = [];
    router.get(route('action-items.index'), { ...filters, view: props.view }, { preserveState: true, replace: true });
}

function resetFilters() {
    selectedIds.value = [];
    Object.assign(filters, { search: '', status: '', pic: '', division: '', overdue: '', owner: '' });
    applyFilters();
}

function switchView(view: 'list' | 'board') {
    selectedIds.value = [];
    router.get(route('action-items.index'), { ...filters, view }, { preserveState: true, replace: true });
}

const boardColumns = [
    { key: 'new', label: 'Baru' },
    { key: 'in_progress', label: 'Berjalan' },
    { key: 'done', label: 'Selesai' },
    { key: 'cancelled', label: 'Dibatalkan' },
];

function updateStatus(task: ActionItem, status: string | number) {
    if (task.status === 'new' && String(status) === 'in_progress') {
        feedbackPendingStatus.value = 'in_progress';
        feedbackTaskId.value = task.id;
        return;
    }

    router.patch(route('action-items.status', task.id), { status: String(status) }, { preserveScroll: true });
}

function canEdit(task: ActionItem) {
    return props.canUpdate || task.pic?.id === page.props.auth.user.id;
}
</script>

<template>
    <Head title="Action Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Action Item" description="Pantau tugas dari seluruh notulen yang dapat Anda akses.">
                <template #actions>
                    <div class="inline-flex rounded-md border border-border bg-surface p-1 shadow-soft-xs">
                        <Button
                            :variant="props.view === 'list' ? 'secondary' : 'ghost'"
                            size="sm"
                            :aria-pressed="props.view === 'list'"
                            @click="switchView('list')"
                        >
                            Daftar
                        </Button>
                        <Button
                            :variant="props.view === 'board' ? 'secondary' : 'ghost'"
                            size="sm"
                            :aria-pressed="props.view === 'board'"
                            @click="switchView('board')"
                        >
                            Board
                        </Button>
                    </div>
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

            <form class="rounded-lg border border-border/80 bg-card p-card shadow-soft-sm" @submit.prevent="applyFilters">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="task-search">Cari</Label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-subtle-foreground"
                                aria-hidden="true"
                            />
                            <Input id="task-search" v-model="filters.search" placeholder="Judul tugas" class="pl-9" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-status">Status</Label>
                        <Select id="task-status" v-model="filters.status">
                            <option value="">Semua status</option>
                            <option v-for="(label, value) in actionStatusLabels" :key="value" :value="value">{{ label }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-pic">PIC</Label>
                        <Select id="task-pic" v-model="filters.pic">
                            <option value="">Semua PIC</option>
                            <option v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-division">Divisi</Label>
                        <Select id="task-division" v-model="filters.division">
                            <option value="">Semua divisi</option>
                            <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-overdue">Tenggat</Label>
                        <Select id="task-overdue" v-model="filters.overdue">
                            <option value="">Semua tenggat</option>
                            <option value="1">Hanya terlambat</option>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="task-owner">Owner</Label>
                        <Select id="task-owner" v-model="filters.owner">
                            <option value="">Semua owner</option>
                            <option value="1">Sudah ada owner</option>
                            <option value="0">Belum ada owner</option>
                        </Select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border/60 pt-4">
                    <Button type="button" variant="outline" @click="resetFilters">
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Reset
                    </Button>
                    <Button type="submit">
                        <Search class="size-4" aria-hidden="true" />
                        Terapkan filter
                    </Button>
                </div>
            </form>

            <template v-if="props.view !== 'board'">
                <EmptyState
                    v-if="props.items.data.length === 0"
                    :icon="ListChecks"
                    title="Tidak ada tugas"
                    description="Ubah filter atau tambahkan action item dari halaman notulen."
                >
                    <div class="flex flex-wrap justify-center gap-2">
                        <Button variant="outline" @click="resetFilters">Reset filter</Button>
                        <Button as-child>
                            <Link :href="route('meetings.index')">Buka dokumen</Link>
                        </Button>
                    </div>
                </EmptyState>

                <template v-else>
                    <div
                        v-if="selectedIds.length > 0"
                        class="flex flex-wrap items-center gap-2 rounded-md border border-border/80 bg-surface p-3 shadow-soft-xs"
                    >
                        <p class="mr-auto text-sm font-medium">{{ selectedIds.length }} tugas dipilih</p>
                        <Select v-model="bulkStatus" class="h-9 w-40 text-xs" aria-label="Status massal">
                            <option value="">Pilih status</option>
                            <option value="new">Baru</option>
                            <option value="in_progress">Berjalan</option>
                            <option value="done">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </Select>
                        <Button type="button" :disabled="!bulkStatus" :loading="bulkProcessing" @click="applyBulkStatus">Terapkan</Button>
                        <Button type="button" variant="ghost" @click="selectedIds = []">Bersihkan</Button>
                    </div>

                    <div class="hidden overflow-x-auto rounded-lg border border-border/80 bg-card shadow-soft-sm md:block">
                        <table class="w-full min-w-[900px] text-sm">
                            <thead class="border-b border-border/60 text-left">
                                <tr class="text-xs font-medium text-subtle-foreground">
                                    <th class="w-10 p-3">
                                        <input
                                            type="checkbox"
                                            class="size-4 rounded border-input accent-primary"
                                            :checked="allSelected"
                                            aria-label="Pilih semua tugas"
                                            @change="toggleAll"
                                        />
                                    </th>
                                    <th class="p-3">Tugas</th>
                                    <th class="p-3">Notulen</th>
                                    <th class="p-3">PIC</th>
                                    <th class="p-3">Tenggat</th>
                                    <th class="p-3">Respons</th>
                                    <th class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="task in props.items.data" :key="task.id" class="transition-colors hover:bg-muted/20">
                                    <td class="p-3">
                                        <input
                                            v-model="selectedIds"
                                            type="checkbox"
                                            :value="task.id"
                                            class="size-4 rounded border-input accent-primary"
                                            :aria-label="`Pilih ${task.title}`"
                                        />
                                    </td>
                                    <td class="p-3">
                                        <p class="font-medium leading-snug">{{ task.title }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ task.division?.name ?? 'Tanpa divisi' }}</p>
                                        <Button size="sm" variant="ghost" class="mt-1 h-7 px-2 text-xs" @click="openFeedback(task)">
                                            <MessageSquare class="size-3.5" aria-hidden="true" />
                                            {{ task.feedback?.length ?? 0 }} respons
                                        </Button>
                                    </td>
                                    <td class="p-3">
                                        <Link
                                            v-if="task.meeting"
                                            :href="route('meetings.show', task.meeting.id)"
                                            class="text-primary hover:underline"
                                        >
                                            {{ task.meeting.title }}
                                        </Link>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="p-3">{{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada owner' }}</td>
                                    <td class="p-3">
                                        <div class="space-y-1">
                                            <span :class="task.is_overdue ? 'font-medium text-destructive-strong' : ''">{{
                                                formatDate(task.due_date)
                                            }}</span>
                                            <Badge v-if="task.is_overdue" variant="danger" class="gap-1">
                                                <AlertTriangle class="size-3" aria-hidden="true" />
                                                Terlambat {{ task.overdue_hours ?? 0 }} jam
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <div class="space-y-1">
                                            <span
                                                v-if="task.response_time_hours !== null && task.response_time_hours !== undefined"
                                                class="text-xs font-medium"
                                            >
                                                {{ task.response_time_hours }} jam
                                            </span>
                                            <span v-else class="text-xs text-muted-foreground">Belum ada</span>
                                            <p
                                                class="text-[11px]"
                                                :class="task.is_response_overdue ? 'font-medium text-destructive-strong' : 'text-muted-foreground'"
                                            >
                                                Batas:
                                                {{ task.response_due_at ? formatDateTime(task.response_due_at) : `${task.response_hours ?? 24} jam` }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <Select
                                            v-if="canEdit(task)"
                                            :model-value="task.status"
                                            class="h-8 text-xs"
                                            aria-label="Status tugas"
                                            @update:model-value="updateStatus(task, $event)"
                                        >
                                            <option value="new">Baru</option>
                                            <option value="in_progress">Berjalan</option>
                                            <option value="done">Selesai</option>
                                            <option value="cancelled">Dibatalkan</option>
                                        </Select>
                                        <StatusBadge v-else kind="action" :value="task.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-2 md:hidden">
                        <div v-for="task in props.items.data" :key="task.id" class="rounded-lg border border-border/80 bg-card p-3 shadow-soft-sm">
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="selectedIds"
                                    type="checkbox"
                                    :value="task.id"
                                    class="mt-1 size-4 shrink-0 rounded border-input accent-primary"
                                    :aria-label="`Pilih ${task.title}`"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium leading-snug">{{ task.title }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ task.division?.name ?? 'Tanpa divisi' }}</p>
                                </div>
                                <Badge v-if="task.is_overdue" variant="danger" class="gap-1">
                                    <AlertTriangle class="size-3" aria-hidden="true" />
                                    Terlambat {{ task.overdue_hours ?? 0 }} jam
                                </Badge>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-muted-foreground">PIC / owner</p>
                                    <p class="font-medium">{{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada owner' }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Tenggat</p>
                                    <p :class="task.is_overdue ? 'font-medium text-destructive-strong' : 'font-medium'">
                                        {{ formatDate(task.due_date) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Response time</p>
                                    <p class="font-medium">
                                        {{
                                            task.response_time_hours !== null && task.response_time_hours !== undefined
                                                ? `${task.response_time_hours} jam`
                                                : 'Belum ada'
                                        }}
                                    </p>
                                    <p
                                        class="mt-0.5"
                                        :class="task.is_response_overdue ? 'font-medium text-destructive-strong' : 'text-muted-foreground'"
                                    >
                                        Batas:
                                        {{ task.response_due_at ? formatDateTime(task.response_due_at) : `${task.response_hours ?? 24} jam` }}
                                    </p>
                                </div>
                            </div>

                            <Link
                                v-if="task.meeting"
                                :href="route('meetings.show', task.meeting.id)"
                                class="mt-2 block truncate text-xs text-primary hover:underline"
                            >
                                {{ task.meeting.title }}
                            </Link>

                            <div class="mt-3 flex items-center gap-2">
                                <Button size="sm" variant="outline" class="h-8 text-xs" @click="openFeedback(task)">
                                    <MessageSquare class="size-3.5" aria-hidden="true" />
                                    {{ task.feedback?.length ?? 0 }} respons
                                </Button>
                                <Select
                                    v-if="canEdit(task)"
                                    :model-value="task.status"
                                    class="h-8 text-xs"
                                    aria-label="Status tugas"
                                    @update:model-value="updateStatus(task, $event)"
                                >
                                    <option value="new">Baru</option>
                                    <option value="in_progress">Berjalan</option>
                                    <option value="done">Selesai</option>
                                    <option value="cancelled">Dibatalkan</option>
                                </Select>
                                <StatusBadge v-else kind="action" :value="task.status" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">
                            Menampilkan {{ props.items.from ?? 0 }}–{{ props.items.to ?? 0 }} dari {{ props.items.total }} tugas
                        </p>
                        <Pagination :links="props.items.links" />
                    </div>
                </template>
            </template>

            <div v-if="props.view === 'board'" class="grid gap-3 lg:grid-cols-4">
                <div v-for="column in boardColumns" :key="column.key" class="space-y-2">
                    <div class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2 text-sm font-medium">
                        <span>{{ column.label }}</span>
                        <span class="text-xs tabular-nums text-muted-foreground">{{ props.board?.[column.key]?.length ?? 0 }}</span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="task in props.board?.[column.key] ?? []"
                            :key="task.id"
                            class="rounded-md border border-border/80 bg-card p-3 text-sm shadow-soft-xs"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-medium leading-snug">{{ task.title }}</p>
                                <Badge v-if="task.is_overdue" variant="danger" class="gap-1" aria-label="Terlambat">
                                    <AlertTriangle class="size-3" aria-hidden="true" />
                                </Badge>
                            </div>
                            <p class="mt-1 truncate text-xs text-muted-foreground">{{ task.meeting?.title }}</p>
                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                <span class="truncate">{{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada owner' }}</span>
                                <span :class="task.is_overdue ? 'font-medium text-destructive-strong' : ''">{{ formatDate(task.due_date) }}</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span>
                                    Respons:
                                    {{
                                        task.response_time_hours !== null && task.response_time_hours !== undefined
                                            ? `${task.response_time_hours} jam`
                                            : 'Belum ada'
                                    }}
                                </span>
                                <span :class="task.is_response_overdue ? 'font-medium text-destructive-strong' : ''">
                                    Batas:
                                    {{ task.response_due_at ? formatDateTime(task.response_due_at) : `${task.response_hours ?? 24} jam` }}
                                </span>
                                <span v-if="task.is_overdue" class="font-medium text-destructive-strong"
                                    >Terlambat {{ task.overdue_hours ?? 0 }} jam</span
                                >
                            </div>
                            <Button size="sm" variant="ghost" class="mt-2 h-7 px-2 text-xs" @click="openFeedback(task)">
                                <MessageSquare class="size-3.5" aria-hidden="true" />
                                {{ task.feedback?.length ?? 0 }} respons
                            </Button>
                            <Select
                                v-if="canEdit(task)"
                                :model-value="task.status"
                                class="mt-2 h-8 text-xs"
                                aria-label="Status tugas"
                                @update:model-value="updateStatus(task, $event)"
                            >
                                <option value="new">Baru</option>
                                <option value="in_progress">Berjalan</option>
                                <option value="done">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </Select>
                        </div>
                        <p
                            v-if="(props.board?.[column.key]?.length ?? 0) === 0"
                            class="rounded-md border border-dashed border-border p-3 text-center text-xs text-muted-foreground"
                        >
                            Kosong
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <ActionItemFeedbackDialog
            :open="feedbackTaskId !== null"
            :task="selectedFeedbackTask"
            :can-respond="selectedFeedbackTask ? canRespond(selectedFeedbackTask) : false"
            :pending-status="feedbackPendingStatus"
            @update:open="
                (open) => {
                    if (!open) {
                        feedbackTaskId = null;
                        feedbackPendingStatus = null;
                    }
                }
            "
        />
    </AppLayout>
</template>
