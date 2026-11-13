<script setup lang="ts">
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Decision, type Meeting } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, FileText, Hash, ScanText } from 'lucide-vue-next';

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
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader :title="`#${props.topic.name}`" :description="props.topic.description ?? 'Belum ada deskripsi topik.'">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('meetings.index', { topic: props.topic.id })">
                            <FileText class="size-4" />
                            Semua dokumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-topic-surface px-2.5 py-1 text-xs font-semibold text-topic">
                    <Hash class="size-3.5" aria-hidden="true" />
                    Topik
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                    <FileText class="size-3.5" aria-hidden="true" />
                    {{ props.meetings.length }} notulen
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    {{ props.decisions.length }} keputusan
                </span>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0 space-y-6">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle as="h2" class="text-base">Notulen dengan topik ini</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <EmptyState
                                v-if="props.meetings.length === 0"
                                title="Belum ada notulen"
                                description="Topik ini belum dipakai pada notulen yang dapat Anda akses."
                            />

                            <div v-else class="space-y-2">
                                <Link
                                    v-for="meeting in props.meetings"
                                    :key="meeting.id"
                                    :href="route('meetings.show', meeting.id)"
                                    class="group flex flex-col gap-2 rounded-lg border border-border p-3.5 transition-colors duration-fast ease-ui hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-foreground transition-colors duration-fast group-hover:text-primary"
                                        >
                                            {{ meeting.title }}
                                        </p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            <DocumentTypeBadge :type="meeting.document_type" />
                                            <span class="text-xs text-muted-foreground">{{
                                                meetingTypeLabels[meeting.meeting_type] ?? meeting.meeting_type
                                            }}</span>
                                            <span aria-hidden="true">·</span>
                                            <span class="inline-flex items-center gap-1">
                                                <CalendarDays class="size-3.5" aria-hidden="true" />
                                                {{ formatDateTime(meeting.scheduled_at) }}
                                            </span>
                                            <span v-if="meeting.division" aria-hidden="true">·</span>
                                            <span v-if="meeting.division">{{ meeting.division.name }}</span>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        <StatusBadge kind="meeting" :value="meeting.status" />
                                        <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-start justify-between gap-3 pb-3">
                            <div>
                                <CardTitle as="h2" class="text-base">Keputusan</CardTitle>
                                <p class="text-sm text-muted-foreground">Keputusan lintas notulen yang membahas topik ini.</p>
                            </div>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground">{{
                                props.decisions.length
                            }}</span>
                        </CardHeader>
                        <CardContent>
                            <EmptyState
                                v-if="props.decisions.length === 0"
                                title="Belum ada keputusan"
                                description="Keputusan akan muncul di sini setelah dicatat pada notulen terkait."
                            />

                            <ol v-else class="relative space-y-4 border-l border-border pl-5">
                                <li v-for="decision in props.decisions" :key="decision.id" class="relative">
                                    <span
                                        class="absolute -left-[27px] top-1.5 size-3 rounded-full border-2 border-card bg-topic"
                                        aria-hidden="true"
                                    ></span>
                                    <p class="text-sm leading-6">{{ decision.content }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                        <span v-if="decision.decided_at">{{ formatDate(decision.decided_at) }}</span>
                                        <span v-if="decision.meeting" aria-hidden="true">·</span>
                                        <Link
                                            v-if="decision.meeting"
                                            :href="route('meetings.show', decision.meeting.id)"
                                            class="text-primary underline underline-offset-2"
                                        >
                                            {{ decision.meeting.title }}
                                        </Link>
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>
                </div>

                <aside class="min-w-0 space-y-4">
                    <section class="overflow-hidden rounded-lg border border-ai/25 bg-ai-surface/60 shadow-card" aria-labelledby="topic-ai-heading">
                        <div class="flex gap-3 border-b border-ai/15 p-5">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-ai/10 text-ai">
                                <ScanText class="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 id="topic-ai-heading" class="text-sm font-semibold">Resume lintas notulen</h2>
                                    <span class="rounded-full bg-card/80 px-2 py-0.5 text-[11px] font-medium text-ai ring-1 ring-ai/20"
                                        >Dibuat oleh AI</span
                                    >
                                </div>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">Ringkasan dari notulen yang telah disetujui.</p>
                            </div>
                        </div>
                        <div class="space-y-3 p-5">
                            <p v-if="props.summaries.length === 0" class="text-sm leading-6 text-muted-foreground">
                                Belum ada resume lintas notulen yang disetujui.
                            </p>
                            <article v-for="summary in props.summaries" :key="summary.id" class="rounded-md border border-ai/15 bg-card/85 p-3.5">
                                <p class="text-xs text-muted-foreground">
                                    Versi {{ summary.version }} · disetujui {{ formatDate(summary.approved_at) }}
                                </p>
                                <p class="mt-1.5 text-sm leading-6">{{ summary.content.ringkasan }}</p>
                            </article>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
