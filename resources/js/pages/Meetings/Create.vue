<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { computed } from 'vue';

interface Template {
    id: number;
    name: string;
    meeting_type: string;
    description: string | null;
}

const props = defineProps<{
    templates: Template[];
    divisions: Division[];
    users: (Pick<User, 'id' | 'name' | 'email'> & { division_id: number | null })[];
    topics: { id: number; name: string }[];
    defaultContent: JSONContent;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notulen', href: '/notulen' },
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

function submit() {
    form.post(route('meetings.store'));
}

const participantCount = computed(() => form.participant_ids.length);
</script>

<template>
    <Head title="Buat notulen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="flex flex-col gap-6 p-4" @submit.prevent="submit">
            <PageHeader title="Buat notulen" description="Mulai dari template standar; Anda dapat menyunting seluruh isi setelahnya.">
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
                        <Label for="title">Judul rapat</Label>
                        <Input id="title" v-model="form.title" placeholder="Contoh: Rapat koordinasi mingguan divisi IT" />
                        <p v-if="form.errors.title" class="text-xs text-rose-600">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="meeting_type">Jenis rapat</Label>
                            <select id="meeting_type" v-model="form.meeting_type" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="template">Template</Label>
                            <select id="template" v-model="form.meeting_template_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="">Tanpa template</option>
                                <option v-for="template in props.templates" :key="template.id" :value="String(template.id)">
                                    {{ template.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="agenda">Agenda</Label>
                        <textarea
                            id="agenda"
                            v-model="form.agenda"
                            rows="3"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Poin agenda yang dibahas"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Isi notulen</Label>
                        <MeetingEditor v-model="form.content" :users="props.users" :divisions="props.divisions" :topics="props.topics" />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2 rounded-lg border bg-card p-4">
                        <Label for="scheduled_at">Jadwal</Label>
                        <Input id="scheduled_at" v-model="form.scheduled_at" type="datetime-local" />
                        <p v-if="form.errors.scheduled_at" class="text-xs text-rose-600">{{ form.errors.scheduled_at }}</p>

                        <Label for="division_id" class="pt-2">Divisi penyelenggara</Label>
                        <select id="division_id" v-model="form.division_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                            <option value="">Tanpa divisi</option>
                            <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                        </select>

                        <Label for="location" class="pt-2">Lokasi</Label>
                        <Input id="location" v-model="form.location" placeholder="Ruang rapat / daring" />

                        <Label for="meeting_link" class="pt-2">Tautan rapat</Label>
                        <Input id="meeting_link" v-model="form.meeting_link" placeholder="https://…" />
                        <p v-if="form.errors.meeting_link" class="text-xs text-rose-600">{{ form.errors.meeting_link }}</p>

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
                        <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            <label v-for="user in props.users" :key="user.id" class="flex items-center gap-2 text-sm">
                                <input v-model="form.participant_ids" type="checkbox" :value="user.id" class="size-4 rounded border" />
                                <span class="flex-1 truncate">{{ user.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
