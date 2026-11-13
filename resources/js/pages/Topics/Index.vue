<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, FileText, Hash, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface TopicRow {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    meetings_count: number;
    decisions_count: number;
}

const props = defineProps<{
    topics: TopicRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Topik', href: '/topik' }];

const query = ref('');
const filteredTopics = computed(() => {
    const normalized = query.value.trim().toLowerCase();

    if (!normalized) {
        return props.topics;
    }

    return props.topics.filter(
        (topic) => topic.name.toLowerCase().includes(normalized) || (topic.description ?? '').toLowerCase().includes(normalized),
    );
});

const totalMeetings = computed(() => props.topics.reduce((total, topic) => total + topic.meetings_count, 0));
const totalDecisions = computed(() => props.topics.reduce((total, topic) => total + topic.decisions_count, 0));

function resetSearch() {
    query.value = '';
}
</script>

<template>
    <Head title="Topik" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader title="Topik" description="Telusuri keputusan dan notulen lintas divisi berdasarkan topik." />

            <section class="grid gap-3 sm:grid-cols-3" aria-label="Ringkasan topik">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Topik aktif</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums">{{ props.topics.length }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Kaitan notulen</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums">{{ totalMeetings }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Keputusan tercatat</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums">{{ totalDecisions }}</p>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <label for="topic-search" class="sr-only">Cari topik</label>
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input
                        id="topic-search"
                        v-model="query"
                        type="search"
                        placeholder="Cari topik"
                        class="pl-9 pr-9 [&::-webkit-search-cancel-button]:hidden"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-1 text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label="Hapus pencarian"
                        @click="resetSearch"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <p class="text-xs text-muted-foreground" aria-live="polite">{{ filteredTopics.length }} dari {{ props.topics.length }} topik</p>
            </div>

            <EmptyState
                v-if="props.topics.length === 0"
                title="Belum ada topik"
                description="Admin dapat menambahkan topik melalui menu Administrasi."
            />

            <EmptyState v-else-if="filteredTopics.length === 0" title="Topik tidak ditemukan" description="Coba kata kunci lain.">
                <Button variant="outline" class="mt-3" @click="resetSearch">Reset pencarian</Button>
            </EmptyState>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="topic in filteredTopics"
                    :key="topic.id"
                    :href="route('topics.show', topic.slug)"
                    class="group block rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <Card class="h-full transition-colors duration-fast ease-ui group-hover:border-primary/40">
                        <CardContent class="flex h-full flex-col gap-3 p-5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-topic-surface text-topic">
                                    <Hash class="size-5" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-foreground transition-colors duration-fast group-hover:text-primary"
                                    >
                                        {{ topic.name }}
                                    </p>
                                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-muted-foreground">
                                        {{ topic.description || 'Belum ada deskripsi.' }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-border pt-3 text-xs text-muted-foreground"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <FileText class="size-3.5" aria-hidden="true" />
                                    <span class="tabular-nums">{{ topic.meetings_count }}</span> notulen
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                                    <span class="tabular-nums">{{ topic.decisions_count }}</span> keputusan
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
