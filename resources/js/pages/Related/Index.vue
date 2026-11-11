<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
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
                    <CardHeader>
                        <CardTitle>Notulen yang menyebut saya</CardTitle>
                        <CardDescription>Dokumen yang memuat mention nama atau divisi Anda.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <EmptyState v-if="props.meetings.length === 0" compact :icon="Inbox" title="Belum ada notulen terkait" />
                        <Link
                            v-for="meeting in props.meetings"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="group block rounded-md border border-border/70 p-3 transition-colors duration-fast ease-out-soft hover:border-primary/20 hover:bg-accent/40"
                        >
                            <p class="font-medium leading-snug transition-colors group-hover:text-primary">{{ meeting.title }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                {{ meetingTypeLabels[meeting.meeting_type] }} · {{ formatDateTime(meeting.scheduled_at) }} ·
                                {{ meeting.division?.name ?? 'Tanpa divisi' }}
                            </p>
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tugas saya dan divisi saya</CardTitle>
                        <CardDescription>Action item yang menjadi tanggung jawab Anda.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState v-if="props.tasks.length === 0" compact :icon="ListChecks" title="Tidak ada tugas" />
                        <div v-else class="divide-y divide-border/60">
                            <Link
                                v-for="task in props.tasks"
                                :key="task.id"
                                :href="route('meetings.show', task.meeting?.id ?? 0)"
                                class="flex items-start justify-between gap-3 py-3 transition-colors hover:text-primary"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium leading-snug">{{ task.title }}</p>
                                    <p class="mt-0.5 text-xs leading-relaxed text-muted-foreground">
                                        {{ task.meeting?.title }} · tenggat {{ formatDate(task.due_date) }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <StatusBadge kind="action" :value="task.status" />
                                    <Badge v-if="task.is_overdue" variant="danger" class="gap-1">
                                        <AlertTriangle class="size-3" aria-hidden="true" />
                                        Terlambat
                                    </Badge>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
