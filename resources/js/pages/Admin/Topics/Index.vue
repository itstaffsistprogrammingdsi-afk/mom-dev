<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

interface TopicRow {
    id: number;
    name: string;
    description: string | null;
    status: 'active' | 'archived';
    meetings_count: number;
}

const props = defineProps<{
    topics: TopicRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Topik', href: '/admin/topik' }];

const form = useForm({ name: '', description: '' });

function submit() {
    form.post(route('admin.topics.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function toggleStatus(topic: TopicRow) {
    router.patch(route('admin.topics.toggle', topic.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kelola topik" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Topik" description="Topik terkelola mencegah duplikasi dan menjaga akurasi pencarian." />

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="h-fit">
                    <CardHeader><CardTitle class="text-base">Tambah topik</CardTitle></CardHeader>
                    <CardContent>
                        <form class="space-y-3" @submit.prevent="submit">
                            <div class="space-y-1">
                                <Label for="topic-name">Nama</Label>
                                <Input id="topic-name" v-model="form.name" placeholder="Contoh: Anggaran" />
                                <p v-if="form.errors.name" class="text-xs font-medium text-destructive-strong">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label for="topic-description">Deskripsi</Label>
                                <Textarea id="topic-description" v-model="form.description" rows="2" />
                            </div>
                            <Button type="submit" class="w-full" :loading="form.processing">Simpan topik</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card class="min-w-0 lg:col-span-2">
                    <CardContent class="min-w-0 overflow-x-auto p-0">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="border-b border-border/60 text-left">
                                <tr class="text-xs font-medium text-subtle-foreground">
                                    <th class="p-3">Topik</th>
                                    <th class="p-3">Notulen</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="topic in props.topics" :key="topic.id" class="transition-colors hover:bg-muted/20">
                                    <td class="p-3">
                                        <p class="font-medium">#{{ topic.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ topic.description || '-' }}</p>
                                    </td>
                                    <td class="p-3">{{ topic.meetings_count }}</td>
                                    <td class="p-3">
                                        <Badge :variant="topic.status === 'active' ? 'success' : 'neutral'">
                                            {{ topic.status === 'active' ? 'Aktif' : 'Arsip' }}
                                        </Badge>
                                    </td>
                                    <td class="p-3 text-right">
                                        <Button variant="outline" size="sm" @click="toggleStatus(topic)">
                                            {{ topic.status === 'active' ? 'Arsipkan' : 'Aktifkan' }}
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
