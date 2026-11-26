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
import { useI18n } from 'vue-i18n';

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
const { t, locale } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const today = new Intl.DateTimeFormat(locale.value, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: page.props.preferences?.timezone ?? 'Asia/Jakarta',
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
        label: t('dashboard.labels.approval'),
        title: meeting.title,
        meta: meeting.division?.name ?? 'Tanpa divisi',
        href: route('meetings.show', meeting.id),
    })),
    ...props.drafts.map((meeting) => ({
        key: `draft-${meeting.id}`,
        label: t('dashboard.labels.draft'),
        title: meeting.title,
        meta: meeting.division?.name ?? 'Tanpa divisi',
        href: route('meetings.show', meeting.id),
    })),
    ...props.divisionTasks.map((task) => ({
        key: `task-${task.id}`,
        label: t('dashboard.labels.division'),
        title: task.title,
        meta: `${task.pic?.name ?? '—'} · ${formatDate(task.due_date)}`,
        href: task.meeting ? route('meetings.show', task.meeting.id) : route('action-items.index'),
    })),
].slice(0, 6));

const statItems = computed(() => [
    { label: t('dashboard.stats.meetings'), value: props.stats.meetings_this_month },
    { label: t('dashboard.stats.openTasks'), value: props.stats.open_tasks },
    {
        label: t('dashboard.stats.overdue'),
        value: props.stats.overdue_tasks,
        tone: props.stats.overdue_tasks > 0 ? ('danger' as const) : ('default' as const),
    },
    { label: t('dashboard.stats.topics'), value: props.stats.active_topics },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-5 px-4 py-5 pb-24 md:px-8 md:py-7 md:pb-7">
            <PageHeader :title="t('dashboard.title')" :description="`${today} · ${user.division?.name ?? '—'}`">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('quick-notes.create')">
                            <StickyNote class="size-4" aria-hidden="true" />
                            {{ t('dashboard.newNote') }}
                        </Link>
                    </Button>
                    <Button v-if="user.role === 'admin' || user.role === 'notulis'" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            {{ t('dashboard.newMeeting') }}
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Card v-if="attentionItems.length" class="border-border/80 shadow-none">
                <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <AlertTriangle class="size-4 text-muted-foreground" aria-hidden="true" />
                        {{ t('dashboard.attention') }}
                    </CardTitle>
                    <span class="text-xs tabular-nums text-muted-foreground">{{ t('dashboard.attentionCount', { n: attentionItems.length }) }}</span>
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
                        <CardTitle class="text-base">{{ t('dashboard.upcoming') }}</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('meetings.index')">{{ t('common.viewAll') }}</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <EmptyState
                            v-if="props.upcoming.length === 0"
                            compact
                            :title="t('dashboard.emptyUpcoming')"
                            :description="t('dashboard.emptyUpcomingBody')"
                        >
                            <Button v-if="user.role === 'admin' || user.role === 'notulis'" size="sm" variant="outline" as-child>
                                <Link :href="route('meetings.create')">{{ t('dashboard.newMeeting') }}</Link>
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
                        <CardTitle class="text-base">{{ t('dashboard.myTasks') }}</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('action-items.index')">{{ t('dashboard.allTasks') }}</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <EmptyState
                            v-if="props.myTasks.length === 0"
                            compact
                            :title="t('dashboard.emptyTasks')"
                            :description="t('dashboard.emptyTasksBody')"
                        />
                        <Link
                            v-for="task in props.myTasks"
                            :key="task.id"
                            :href="route('meetings.show', task.meeting?.id ?? 0)"
                            class="group flex items-start justify-between gap-3 rounded-lg px-2 py-3 transition-colors duration-fast hover:bg-muted/50"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="truncate text-sm font-medium leading-snug group-hover:text-primary">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ task.meeting?.title }} · {{ t('dashboard.labels.due', { date: formatDate(task.due_date) }) }}</p>
                            </div>
                            <span v-if="task.is_overdue" class="shrink-0 text-[11px] font-medium text-destructive-strong">{{ t('tasks.stats.overdue') }}</span>
                            <StatusBadge v-else kind="action" :value="task.status" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-border/80 shadow-none">
                <CardHeader class="flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
                        {{ t('dashboard.recent') }}
                    </CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('meetings.index')">{{ t('dashboard.openList') }}</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="props.recent.length === 0"
                        compact
                        :title="t('dashboard.emptyDocuments')"
                        :description="t('dashboard.emptyDocumentsBody')"
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
