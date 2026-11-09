<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Hash } from 'lucide-vue-next';

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
</script>

<template>
    <Head title="Topik" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Topik" description="Telusuri keputusan dan notulen lintas divisi berdasarkan topik." />

            <EmptyState
                v-if="props.topics.length === 0"
                title="Belum ada topik"
                description="Admin dapat menambahkan topik melalui menu Administrasi."
            />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Link v-for="topic in props.topics" :key="topic.id" :href="route('topics.show', topic.slug)">
                    <Card class="h-full transition hover:border-primary/40 hover:shadow-md">
                        <CardContent class="space-y-2 p-5">
                            <div class="flex items-center gap-2">
                                <Hash class="size-4 text-muted-foreground" />
                                <p class="font-semibold">{{ topic.name }}</p>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ topic.description || 'Belum ada deskripsi.' }}</p>
                            <div class="flex gap-3 pt-2 text-xs text-muted-foreground">
                                <span>{{ topic.meetings_count }} notulen</span>
                                <span>{{ topic.decisions_count }} keputusan</span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
