<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Meeting } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Inbox, ListChecks } from 'lucide-vue-next';

const props = defineProps<{
    meetings: Meeting[];
    tasks: ActionItem[];
    summary: { meetings: number; open_tasks: number; overdue_tasks: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Terkait Saya', href: '/terkait-saya' }];
</script>

<template>
    <Head title="Terkait Saya" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Terkait Saya" description="Semua notulen dan tugas yang menyebut Anda atau divisi Anda." />

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard label="Notulen terkait" :value="props.summary.meetings" :icon="Inbox" />
                <StatCard label="Tugas terbuka" :value="props.summary.open_tasks" :icon="ListChecks" />
                <StatCard
                    label="Tugas terlambat"
                    :value="props.summary.overdue_tasks"
                    :icon="AlertTriangle"
                    :tone="props.summary.overdue_tasks > 0 ? 'danger' : 'success'"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Notulen yang menyebut saya</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <EmptyState v-if="props.meetings.length === 0" title="Belum ada notulen terkait" />
                        <Link
                            v-for="meeting in props.meetings"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="block rounded-lg border p-3 transition hover:bg-muted/50"
                        >
                            <p class="font-medium leading-tight">{{ meeting.title }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ meetingTypeLabels[meeting.meeting_type] }} · {{ formatDateTime(meeting.scheduled_at) }} ·
                                {{ meeting.division?.name ?? 'Tanpa divisi' }}
                            </p>
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Tugas saya / divisi saya</CardTitle></CardHeader>
                    <CardContent>
                        <EmptyState v-if="props.tasks.length === 0" title="Tidak ada tugas" />
                        <div v-else class="divide-y">
                            <Link
                                v-for="task in props.tasks"
                                :key="task.id"
                                :href="route('meetings.show', task.meeting?.id ?? 0)"
                                class="flex items-start justify-between gap-3 py-3"
                            >
                                <div>
                                    <p class="font-medium leading-tight">{{ task.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ task.meeting?.title }} · tenggat {{ formatDate(task.due_date) }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <StatusBadge kind="action" :value="task.status" />
                                    <span v-if="task.is_overdue" class="text-xs font-medium text-rose-600">Terlambat</span>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
