<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels, documentTypeClasses, documentTypeLabels, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { computed, watch } from 'vue';

interface Template {
    id: number;
    name: string;
    document_type: string;
    meeting_type: string;
    description: string | null;
    sections: string[] | null;
    content: JSONContent | null;
}

const props = defineProps<{
    templates: Template[];
    divisions: Division[];
    users: (Pick<User, 'id' | 'name' | 'email'> & { division_id: number | null })[];
    topics: { id: number; name: string }[];
    defaultContent: JSONContent;
    documentTypes: { value: string; label: string; description: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dokumen', href: '/notulen' },
    { title: 'Buat', href: '/notulen/buat' },
];

const now = new Date();
const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60_000).toISOString().slice(0, 16);

const form = useForm({
    title: '',
    meeting_type: 'division',
    meeting_template_id: '',
    division_id: '',
    scheduled_at: localNow,
    location: '',
    meeting_link: '',
    agenda: '',
    confidentiality: 'division',
    participant_ids: [] as number[],
    content: props.defaultContent as JSONContent,
});

const selectedTemplate = computed(() => props.templates.find((template) => String(template.id) === String(form.meeting_template_id)));
const participantCount = computed(() => form.participant_ids.length);
const isMeetingDocument = computed(() => !selectedTemplate.value || selectedTemplate.value.document_type === 'meeting');

const groupedTemplates = computed(() => {
    const groups: Record<string, Template[]> = {};

    for (const template of props.templates) {
        groups[template.document_type] ??= [];
        groups[template.document_type].push(template);
    }

    return Object.entries(groups).map(([value, templates]) => ({
        value,
        label: documentTypeLabels[value] ?? value,
        templates,
    }));
});

const divisionUsers = computed(() => {
    if (!form.division_id) {
        return props.users;
    }

    return props.users.filter((user) => user.division_id === Number(form.division_id));
});

const selectedDivision = computed(() => props.divisions.find((division) => String(division.id) === String(form.division_id)));

function selectAllParticipants() {
    form.participant_ids = divisionUsers.value.map((user) => user.id);
}

function clearParticipants() {
    form.participant_ids = [];
}

function submit() {
    form.post(route('meetings.store'));
}

watch(
    () => form.meeting_template_id,
    () => {
        const template = selectedTemplate.value;

        if (!template) {
            form.content = props.defaultContent;

            return;
        }

        form.content = template.content ?? props.defaultContent;

        if (template.meeting_type) {
            form.meeting_type = template.meeting_type;
        }
    },
);

watch(
    () => form.division_id,
    () => {
        form.participant_ids = form.division_id ? divisionUsers.value.map((user) => user.id) : [];
    },
);
</script>

<template>
    <Head title="Buat dokumen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="flex flex-col gap-6 p-4" @submit.prevent="submit">
            <PageHeader title="Buat dokumen" description="Mulai dari template notulen, proposal, laporan, brosur, atau struktur kosong.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('meetings.index')">Batal</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Simpan draf</Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div class="space-y-2">
                        <Label for="title">Judul dokumen</Label>
                        <Input id="title" v-model="form.title" placeholder="Contoh: Rapat koordinasi mingguan divisi IT" />
                        <p v-if="form.errors.title" class="text-xs text-rose-600">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-if="isMeetingDocument" class="space-y-2">
                            <Label for="meeting_type">Jenis rapat</Label>
                            <select id="meeting_type" v-model="form.meeting_type" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div v-else class="space-y-2">
                            <Label>Jenis dokumen</Label>
                            <div class="flex h-9 items-center rounded-md border bg-muted/40 px-3 text-sm">
                                {{ documentTypeLabels[selectedTemplate?.document_type ?? 'other'] }}
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="template">Template</Label>
                            <select id="template" v-model="form.meeting_template_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="">Tanpa template</option>
                                <optgroup v-for="group in groupedTemplates" :key="group.value" :label="group.label">
                                    <option v-for="template in group.templates" :key="template.id" :value="String(template.id)">
                                        {{ template.name }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div v-if="selectedTemplate" class="rounded-lg border bg-muted/30 p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="documentTypeClasses[selectedTemplate.document_type]">
                                {{ documentTypeLabels[selectedTemplate.document_type] ?? selectedTemplate.document_type }}
                            </span>
                            <span class="text-sm font-medium">{{ selectedTemplate.name }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ selectedTemplate.description ?? 'Template siap digunakan.' }}</p>
                        <div v-if="selectedTemplate.sections?.length" class="mt-2 flex flex-wrap gap-1">
                            <span v-for="section in selectedTemplate.sections" :key="section" class="rounded bg-background px-2 py-0.5 text-xs text-muted-foreground">
                                {{ section }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="agenda">Agenda atau ringkasan</Label>
                        <textarea
                            id="agenda"
                            v-model="form.agenda"
                            rows="3"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Poin agenda yang dibahas"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Isi dokumen</Label>
                        <MeetingEditor v-model="form.content" :users="props.users" :divisions="props.divisions" :topics="props.topics" />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2 rounded-lg border bg-card p-4">
                        <Label for="scheduled_at">{{ isMeetingDocument ? 'Jadwal' : 'Tanggal dokumen' }}</Label>
                        <Input id="scheduled_at" v-model="form.scheduled_at" type="datetime-local" />
                        <p v-if="form.errors.scheduled_at" class="text-xs text-rose-600">{{ form.errors.scheduled_at }}</p>

                        <Label for="division_id" class="pt-2">Divisi penyelenggara</Label>
                        <select id="division_id" v-model="form.division_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                            <option value="">Tanpa divisi</option>
                            <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                        </select>

                        <template v-if="isMeetingDocument">
                            <Label for="location" class="pt-2">Lokasi</Label>
                            <Input id="location" v-model="form.location" placeholder="Ruang rapat / daring" />

                            <Label for="meeting_link" class="pt-2">Tautan rapat</Label>
                            <Input id="meeting_link" v-model="form.meeting_link" placeholder="https://…" />
                            <p v-if="form.errors.meeting_link" class="text-xs text-rose-600">{{ form.errors.meeting_link }}</p>
                        </template>

                        <Label for="confidentiality" class="pt-2">Kerahasiaan</Label>
                        <select id="confidentiality" v-model="form.confidentiality" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                            <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>

                    <div class="space-y-2 rounded-lg border bg-card p-4">
                        <div class="flex items-center justify-between">
                            <Label>Peserta</Label>
                            <span class="text-xs text-muted-foreground">{{ participantCount }} dipilih</span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ selectedDivision ? `Anggota ${selectedDivision.name} otomatis dipilih.` : 'Pilih divisi untuk memilih peserta secara otomatis.' }}
                        </p>
                        <div class="flex gap-2">
                            <Button type="button" size="sm" variant="outline" @click="selectAllParticipants">Pilih semua</Button>
                            <Button type="button" size="sm" variant="ghost" @click="clearParticipants">Kosongkan</Button>
                        </div>
                        <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            <label v-for="user in divisionUsers" :key="user.id" class="flex items-center gap-2 text-sm">
                                <input v-model="form.participant_ids" type="checkbox" :value="user.id" class="size-4 rounded border" />
                                <span class="flex-1 truncate">{{ user.name }}</span>
                            </label>
                            <p v-if="divisionUsers.length === 0" class="text-xs text-muted-foreground">Belum ada pengguna pada divisi ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
