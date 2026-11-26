<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatStrip from '@/components/StatStrip.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { documentTypeLabels, formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Meeting, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, ChevronRight, FileText, Plus, StickyNote } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        meetings_this_month: number;
        open_tasks: number;
        overdue_tasks: number;
        active_topics: number;
    };
    upcoming: Meeting[];
    recent: Meeting[];
    myTasks: ActionItem[];
    drafts: Meeting[];
    divisionTasks: ActionItem[];
    approvals: Meeting[];
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const today = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
}).format(new Date());

type AttentionItem = {
    key: string;
    label: string;
    title: string;
    meta: string;
    href: string;
};

const attentionItems = computed<AttentionItem[]>(() => [
    ...props.approvals.map((meeting) => ({
        key: `approval-${meeting.id}`,
        label: 'Menunggu persetujuan',
        title: meeting.title,
        meta: meeting.division?.name ?? 'Tanpa divisi',
        href: route('meetings.show', meeting.id),
    })),
    ...props.drafts.map((meeting) => ({
        key: `draft-${meeting.id}`,
        label: 'Draf perlu diselesaikan',
        title: meeting.title,
        meta: meeting.division?.name ?? 'Tanpa divisi',
        href: route('meetings.show', meeting.id),
    })),
    ...props.divisionTasks.map((task) => ({
        key: `task-${task.id}`,
        label: 'Tindak lanjut divisi',
        title: task.title,
        meta: `${task.pic?.name ?? 'Belum ada PIC'} · ${formatDate(task.due_date)}`,
        href: task.meeting ? route('meetings.show', task.meeting.id) : route('action-items.index'),
    })),
].slice(0, 6));

const statItems = computed(() => [
    { label: 'Rapat bulan ini', value: props.stats.meetings_this_month },
    { label: 'Tindak lanjut terbuka', value: props.stats.open_tasks },
    {
        label: 'Terlambat',
        value: props.stats.overdue_tasks,
        tone: props.stats.overdue_tasks > 0 ? ('danger' as const) : ('default' as const),
    },
    { label: 'Topik aktif', value: props.stats.active_topics },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-5 px-4 py-5 md:px-8 md:py-7">
            <PageHeader title="Hari ini" :description="`${today} · ${user.division?.name ?? 'Belum ada divisi'}`">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('quick-notes.create')">
                            <StickyNote class="size-4" aria-hidden="true" />
                            Catatan cepat
                        </Link>
                    </Button>
                    <Button v-if="user.role === 'admin' || user.role === 'notulis'" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Buat notulen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Card v-if="attentionItems.length" class="border-border/80 shadow-none">
                <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <AlertTriangle class="size-4 text-muted-foreground" aria-hidden="true" />
                        Perlu tindakan
                    </CardTitle>
                    <span class="text-xs tabular-nums text-muted-foreground">{{ attentionItems.length }} item</span>
                </CardHeader>
                <CardContent class="divide-y divide-border/70 pt-0">
                    <Link
                        v-for="item in attentionItems"
                        :key="item.key"
                        :href="item.href"
                        class="group flex items-center gap-3 py-3 transition-colors duration-fast hover:text-primary"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block text-[11px] font-medium text-muted-foreground">{{ item.label }}</span>
                            <span class="mt-0.5 block truncate text-sm font-medium">{{ item.title }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ item.meta }}</span>
                        </span>
                        <ChevronRight class="size-4 shrink-0 text-muted-foreground transition-transform duration-fast group-hover:translate-x-0.5" aria-hidden="true" />
                    </Link>
                </CardContent>
            </Card>

            <StatStrip :items="statItems" />

            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-border/80 shadow-none">
                    <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                        <CardTitle class="text-base">Rapat mendatang</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('meetings.index')">Lihat semua</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <EmptyState
                            v-if="props.upcoming.length === 0"
                            compact
                            title="Belum ada rapat mendatang"
                            description="Buat notulen untuk rapat berikutnya agar semua peserta mendapat konteks yang sama."
                        >
                            <Button v-if="user.role === 'admin' || user.role === 'notulis'" size="sm" variant="outline" as-child>
                                <Link :href="route('meetings.create')">Buat notulen</Link>
                            </Button>
                        </EmptyState>
                        <Link
                            v-for="meeting in props.upcoming"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="group flex items-start justify-between gap-3 rounded-lg px-2 py-3 transition-colors duration-fast hover:bg-muted/50"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="truncate text-sm font-medium leading-snug group-hover:text-primary">{{ meeting.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(meeting.scheduled_at) }}
                                    <span v-if="meeting.division">· {{ meeting.division.name }}</span>
                                </p>
                            </div>
                            <StatusBadge kind="meeting" :value="meeting.status" />
                        </Link>
                    </CardContent>
                </Card>

                <Card class="border-border/80 shadow-none">
                    <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                        <CardTitle class="text-base">Tindak lanjut saya</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('action-items.index')">Semua tugas</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <EmptyState
                            v-if="props.myTasks.length === 0"
                            compact
                            title="Tidak ada tugas terbuka"
                            description="Tugas yang menjadi tanggung jawab Anda akan muncul di sini."
                        />
                        <Link
                            v-for="task in props.myTasks"
                            :key="task.id"
                            :href="route('meetings.show', task.meeting?.id ?? 0)"
                            class="group flex items-start justify-between gap-3 rounded-lg px-2 py-3 transition-colors duration-fast hover:bg-muted/50"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="truncate text-sm font-medium leading-snug group-hover:text-primary">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ task.meeting?.title }} · tenggat {{ formatDate(task.due_date) }}</p>
                            </div>
                            <span v-if="task.is_overdue" class="shrink-0 text-[11px] font-medium text-destructive-strong">Terlambat</span>
                            <StatusBadge v-else kind="action" :value="task.status" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-border/80 shadow-none">
                <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
                        Dokumen terbaru
                    </CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('meetings.index')">Buka daftar</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="props.recent.length === 0"
                        compact
                        title="Belum ada dokumen"
                        description="Mulai dengan membuat dokumen pertama."
                    />
                    <div v-else class="divide-y divide-border/70">
                        <Link
                            v-for="meeting in props.recent"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="group flex flex-wrap items-center justify-between gap-2 py-3 transition-colors hover:text-primary"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ meeting.title }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ documentTypeLabels[meeting.document_type] ?? meetingTypeLabels[meeting.meeting_type] }} ·
                                    {{ formatDate(meeting.scheduled_at) }} · {{ meeting.division?.name ?? 'Tanpa divisi' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                                <StatusBadge kind="meeting" :value="meeting.status" />
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
