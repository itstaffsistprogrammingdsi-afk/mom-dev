<script setup lang="ts">
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Decision, type Meeting } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, FileText, Hash, Sparkles } from 'lucide-vue-next';

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
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader :title="`#${props.topic.name}`" :description="props.topic.description ?? 'Belum ada deskripsi topik.'">
                <template #actions>
                    <Badge :variant="props.topic.status === 'active' ? 'success' : 'neutral'">
                        {{ props.topic.status === 'active' ? 'Aktif' : 'Diarsipkan' }}
                    </Badge>
                    <Button variant="outline" as-child>
                        <Link :href="route('topics.index')">Semua topik</Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
                <div class="min-w-0 space-y-6">
                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Notulen dengan topik ini</CardTitle>
                                <CardDescription class="mt-1">Dokumen lintas divisi yang membahas topik ini.</CardDescription>
                            </div>
                            <Badge variant="neutral">{{ props.meetings.length }}</Badge>
                        </CardHeader>
                        <CardContent>
                            <EmptyState v-if="props.meetings.length === 0" compact :icon="FileText" title="Belum ada notulen" />
                            <div v-else class="space-y-2">
                                <Link
                                    v-for="meeting in props.meetings"
                                    :key="meeting.id"
                                    :href="route('meetings.show', meeting.id)"
                                    class="group flex flex-col gap-2 rounded-md border border-border/70 p-3 transition-colors duration-fast ease-out-soft hover:border-primary/20 hover:bg-accent/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0 space-y-1">
                                        <p class="truncate font-medium leading-snug text-foreground transition-colors group-hover:text-primary">
                                            {{ meeting.title }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                            <DocumentTypeBadge :type="meeting.document_type" />
                                            <span>{{ meetingTypeLabels[meeting.meeting_type] }}</span>
                                            <span aria-hidden="true">·</span>
                                            <span>{{ formatDateTime(meeting.scheduled_at) }}</span>
                                        </div>
                                    </div>
                                    <StatusBadge kind="meeting" :value="meeting.status" />
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Keputusan</CardTitle>
                                <CardDescription class="mt-1">Keputusan yang tercatat dengan topik ini.</CardDescription>
                            </div>
                            <Badge variant="neutral">{{ props.decisions.length }}</Badge>
                        </CardHeader>
                        <CardContent>
                            <EmptyState v-if="props.decisions.length === 0" compact :icon="CheckCircle2" title="Belum ada keputusan" />
                            <ol v-else class="space-y-4">
                                <li v-for="(decision, index) in props.decisions" :key="decision.id" class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-semibold tabular-nums text-accent-foreground"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <div class="min-w-0 space-y-1">
                                        <p class="text-sm leading-relaxed text-foreground">{{ decision.content }}</p>
                                        <p v-if="decision.decided_at" class="text-xs text-muted-foreground">{{ formatDate(decision.decided_at) }}</p>
                                        <Link
                                            v-if="decision.meeting"
                                            :href="route('meetings.show', decision.meeting.id)"
                                            class="inline-flex text-xs font-medium text-primary hover:underline"
                                        >
                                            {{ decision.meeting.title }}
                                        </Link>
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-20">
                    <Card class="border-ai-border bg-ai-soft/40">
                        <CardHeader class="flex-row items-start gap-3 space-y-0">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-surface text-ai-foreground shadow-soft-xs ring-1 ring-ai-border"
                            >
                                <Sparkles class="size-4" aria-hidden="true" />
                            </div>
                            <div>
                                <CardTitle>Resume lintas notulen</CardTitle>
                                <CardDescription class="mt-1">Ringkasan yang sudah disetujui.</CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <EmptyState
                                v-if="props.summaries.length === 0"
                                compact
                                :icon="Sparkles"
                                title="Belum ada resume"
                                description="Resume muncul setelah disetujui pada halaman notulen."
                                class="border-ai-border/70 bg-surface/60"
                            />
                            <div
                                v-for="summary in props.summaries"
                                :key="summary.id"
                                class="space-y-2 rounded-md border border-ai-border/70 bg-surface/80 p-3"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge variant="ai">v{{ summary.version }}</Badge>
                                    <span class="text-xs text-muted-foreground">Disetujui {{ formatDate(summary.approved_at) }}</span>
                                </div>
                                <p class="text-sm leading-relaxed text-foreground">{{ summary.content.ringkasan }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tentang topik</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <Hash class="size-4" aria-hidden="true" />
                                <span class="font-medium text-foreground">#{{ props.topic.name }}</span>
                            </div>
                            <p class="leading-relaxed text-muted-foreground">{{ props.topic.description ?? 'Belum ada deskripsi topik.' }}</p>
                            <div class="grid grid-cols-2 gap-2 border-t border-border/60 pt-3">
                                <div class="rounded-md bg-muted/40 p-2.5">
                                    <p class="text-xs text-muted-foreground">Notulen</p>
                                    <p class="text-lg font-semibold tabular-nums">{{ props.meetings.length }}</p>
                                </div>
                                <div class="rounded-md bg-muted/40 p-2.5">
                                    <p class="text-xs text-muted-foreground">Keputusan</p>
                                    <p class="text-lg font-semibold tabular-nums">{{ props.decisions.length }}</p>
                                </div>
                            </div>
                            <Link
                                :href="route('topics.index')"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                            >
                                <Hash class="size-4" aria-hidden="true" />
                                Telusuri topik lain
                            </Link>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
