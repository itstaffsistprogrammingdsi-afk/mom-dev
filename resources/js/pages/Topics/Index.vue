<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Hash, Search } from 'lucide-vue-next';
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
</script>

<template>
    <Head title="Topik" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Topik" description="Telusuri keputusan dan notulen lintas divisi berdasarkan topik." />

            <div class="max-w-md space-y-1.5">
                <Label for="topic-search">Cari topik</Label>
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-subtle-foreground" aria-hidden="true" />
                    <Input id="topic-search" v-model="query" placeholder="Nama atau deskripsi topik" class="pl-9" />
                </div>
            </div>

            <EmptyState
                v-if="props.topics.length === 0"
                :icon="Hash"
                title="Belum ada topik"
                description="Admin dapat menambahkan topik melalui menu Administrasi."
            />

            <EmptyState
                v-else-if="filteredTopics.length === 0"
                :icon="Hash"
                title="Tidak ada topik yang cocok"
                description="Coba kata kunci lain atau kosongkan pencarian."
            >
                <Button variant="outline" @click="query = ''">Kosongkan pencarian</Button>
            </EmptyState>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="topic in filteredTopics"
                    :key="topic.id"
                    :href="route('topics.show', topic.slug)"
                    class="rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                >
                    <Card interactive class="h-full">
                        <CardContent class="flex h-full flex-col gap-3 p-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground">
                                    <Hash class="size-4" aria-hidden="true" />
                                </div>
                            </div>

                            <div class="space-y-1">
                                <p class="font-semibold leading-snug text-foreground">#{{ topic.name }}</p>
                                <p class="line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                                    {{ topic.description || 'Belum ada deskripsi.' }}
                                </p>
                            </div>

                            <div class="mt-auto flex items-center gap-2 border-t border-border/60 pt-3 text-xs text-muted-foreground">
                                <span class="tabular-nums">{{ topic.meetings_count }} notulen</span>
                                <span aria-hidden="true">·</span>
                                <span class="tabular-nums">{{ topic.decisions_count }} keputusan</span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
