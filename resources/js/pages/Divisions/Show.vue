<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, meetingTypeLabels } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Meeting } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface DivisionDetail {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    users_count: number;
    head?: { id: number; name: string } | null;
}

const props = defineProps<{
    division: DivisionDetail;
    meetings: Meeting[];
    tasks: ActionItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Divisi', href: route('divisions.show', props.division.slug) },
    { title: props.division.name, href: route('divisions.show', props.division.slug) },
];
</script>

<template>
    <Head :title="props.division.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader :title="props.division.name" :description="props.division.description ?? 'Aktivitas dan notulen divisi.'">
                <template #actions>
                    <span class="text-sm text-muted-foreground">{{ props.division.users_count }} anggota</span>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Notulen divisi</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <EmptyState v-if="props.meetings.length === 0" title="Belum ada notulen" />
                        <Link
                            v-for="meeting in props.meetings"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="flex items-center justify-between gap-3 rounded-md border p-3 text-sm hover:bg-muted/40"
                        >
                            <div>
                                <p class="font-medium">{{ meeting.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ meetingTypeLabels[meeting.meeting_type] }} · {{ formatDate(meeting.scheduled_at) }}</p>
                            </div>
                            <StatusBadge kind="meeting" :value="meeting.status" />
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Action item divisi</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <EmptyState v-if="props.tasks.length === 0" title="Tidak ada action item" />
                        <Link
                            v-for="task in props.tasks"
                            :key="task.id"
                            :href="route('action-items.index')"
                            class="flex items-center justify-between gap-3 rounded-md border p-3 text-sm hover:bg-muted/40"
                        >
                            <div>
                                <p class="font-medium">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ task.meeting?.title }} · {{ formatDate(task.due_date) }}</p>
                            </div>
                            <StatusBadge kind="action" :value="task.status" />
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
