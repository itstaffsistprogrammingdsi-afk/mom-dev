<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Topik" description="Topik terkelola mencegah duplikasi dan menjaga akurasi pencarian." />

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="h-fit">
                    <CardHeader><CardTitle class="text-base">Tambah topik</CardTitle></CardHeader>
                    <CardContent>
                        <form class="space-y-3" @submit.prevent="submit">
                            <div class="space-y-1">
                                <Label for="topic-name">Nama</Label>
                                <Input id="topic-name" v-model="form.name" placeholder="Contoh: Anggaran" />
                                <p v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label for="topic-description">Deskripsi</Label>
                                <textarea
                                    id="topic-description"
                                    v-model="form.description"
                                    rows="2"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <Button type="submit" class="w-full" :disabled="form.processing">Simpan topik</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardContent class="overflow-x-auto p-0">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="p-3">Topik</th>
                                    <th class="p-3">Notulen</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="topic in props.topics" :key="topic.id">
                                    <td class="p-3">
                                        <p class="font-medium">#{{ topic.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ topic.description || '—' }}</p>
                                    </td>
                                    <td class="p-3">{{ topic.meetings_count }}</td>
                                    <td class="p-3">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="topic.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700'"
                                        >
                                            {{ topic.status === 'active' ? 'Aktif' : 'Arsip' }}
                                        </span>
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
