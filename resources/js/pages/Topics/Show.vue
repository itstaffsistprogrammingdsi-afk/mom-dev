<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Decision, type Meeting } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface Topic {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: string;
}

interface Summary {
    id: number;
    content: { ringkasan?: string } & Record<string, unknown>;
    version: number;
    approved_at: string | null;
}

const props = defineProps<{
    topic: Topic;
    meetings: Meeting[];
    decisions: (Decision & { meeting?: { id: number; title: string; scheduled_at: string | null } | null })[];
    summaries: Summary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Topik', href: '/topik' },
    { title: props.topic.name, href: `/topik/${props.topic.slug}` },
];
</script>

<template>
    <Head :title="`#${props.topic.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader :title="`#${props.topic.name}`" :description="props.topic.description ?? 'Belum ada deskripsi topik.'" />

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <Card>
                        <CardHeader><CardTitle class="text-base">Notulen dengan topik ini</CardTitle></CardHeader>
                        <CardContent class="space-y-2">
                            <EmptyState v-if="props.meetings.length === 0" title="Belum ada notulen" />
                            <Link
                                v-for="meeting in props.meetings"
                                :key="meeting.id"
                                :href="route('meetings.show', meeting.id)"
                                class="flex items-center justify-between gap-3 rounded-lg border p-3 transition hover:bg-muted/50"
                            >
                                <div>
                                    <p class="font-medium leading-tight">{{ meeting.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ meetingTypeLabels[meeting.meeting_type] }} · {{ formatDateTime(meeting.scheduled_at) }}
                                    </p>
                                </div>
                                <StatusBadge kind="meeting" :value="meeting.status" />
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle class="text-base">Keputusan</CardTitle></CardHeader>
                        <CardContent>
                            <EmptyState v-if="props.decisions.length === 0" title="Belum ada keputusan" />
                            <ol v-else class="list-decimal space-y-3 pl-5 text-sm">
                                <li v-for="decision in props.decisions" :key="decision.id">
                                    {{ decision.content }}
                                    <span v-if="decision.decided_at" class="ml-1 text-xs text-muted-foreground"
                                        >({{ formatDate(decision.decided_at) }})</span
                                    >
                                    <Link
                                        v-if="decision.meeting"
                                        :href="route('meetings.show', decision.meeting.id)"
                                        class="block text-xs text-primary underline"
                                    >
                                        {{ decision.meeting.title }}
                                    </Link>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </div>

                <Card class="h-fit">
                    <CardHeader><CardTitle class="text-base">Resume lintas notulen</CardTitle></CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <p v-if="props.summaries.length === 0" class="text-muted-foreground">Belum ada resume lintas notulen yang disetujui.</p>
                        <div v-for="summary in props.summaries" :key="summary.id" class="space-y-1 rounded-lg border p-3">
                            <p class="text-xs text-muted-foreground">v{{ summary.version }} · disetujui {{ formatDate(summary.approved_at) }}</p>
                            <p>{{ summary.content.ringkasan }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
