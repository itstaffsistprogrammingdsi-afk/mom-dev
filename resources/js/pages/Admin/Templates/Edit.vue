<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

interface Template {
    id: number;
    name: string;
    document_type: string;
    meeting_type: string;
    description: string | null;
    is_active: boolean;
    content?: JSONContent | null;
}

const props = defineProps<{
    template: Template;
    defaultContent: JSONContent;
    documentTypes: { value: string; label: string; description: string }[];
    meetingTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Template Dokumen', href: '/admin/template' },
    { title: props.template.name, href: `/admin/template/${props.template.id}/sunting` },
];

const form = useForm({
    name: props.template.name,
    document_type: props.template.document_type,
    meeting_type: props.template.meeting_type,
    description: props.template.description ?? '',
    is_active: props.template.is_active,
    content: props.template.content ?? props.defaultContent,
});

const selectedType = computed(() => props.documentTypes.find((type) => type.value === form.document_type));

function submit() {
    form.patch(route('admin.templates.update', props.template.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Sunting ${props.template.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="flex flex-col gap-6 p-4" @submit.prevent="submit">
            <PageHeader :title="`Sunting ${props.template.name}`" description="Atur metadata dan struktur isi template.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.templates.index')">
                            <ArrowLeft class="size-4" />
                            Kembali
                        </Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Simpan template</Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-4">
                <div class="space-y-4 lg:col-span-3">
                    <div class="space-y-2">
                        <label for="template-name" class="text-sm font-medium">Nama template</label>
                        <input id="template-name" v-model="form.name" required class="h-9 w-full rounded-md border bg-background px-3 text-sm" />
                        <p v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Isi template</label>
                        <MeetingEditor v-model="form.content" :show-tag-picker="false" />
                        <p class="text-xs text-muted-foreground">
                            Gunakan heading, daftar, dan tabel untuk membentuk struktur dokumen. Teks ini akan otomatis menjadi isi awal saat template
                            dipakai.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <Card>
                        <CardHeader><CardTitle class="text-base">Pengaturan</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="space-y-2">
                                <label for="document-type" class="text-sm font-medium">Jenis dokumen</label>
                                <select
                                    id="document-type"
                                    v-model="form.document_type"
                                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                </select>
                                <p v-if="selectedType" class="text-xs text-muted-foreground">{{ selectedType.description }}</p>
                            </div>

                            <div class="space-y-2">
                                <label for="meeting-type" class="text-sm font-medium">Jenis rapat</label>
                                <select id="meeting-type" v-model="form.meeting_type" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                    <option v-for="type in props.meetingTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label for="description" class="text-sm font-medium">Deskripsi</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                />
                            </div>

                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.is_active" type="checkbox" class="size-4 rounded border" />
                                Aktif dan dapat dipilih pengguna
                            </label>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle class="text-base">Tips struktur</CardTitle></CardHeader>
                        <CardContent class="space-y-2 text-xs text-muted-foreground">
                            <p>Notulen: Agenda, Pembahasan, Keputusan, Action Item.</p>
                            <p>Proposal: Ringkasan, Latar Belakang, Tujuan, Timeline, Anggaran, Risiko.</p>
                            <p>Laporan: Ringkasan Eksekutif, Temuan, Analisis, Rekomendasi.</p>
                            <p>Brosur: Headline, Target Audiens, Keunggulan, CTA, Kontak.</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
