<script setup lang="ts">
import AvatarGroup from '@/components/AvatarGroup.vue';
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels, documentTypeClasses, documentTypeLabels, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { CalendarDays, FileText, Link2, MapPin, Search, ShieldCheck, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

const participantQuery = ref('');
const filteredUsers = computed(() => {
    const query = participantQuery.value.trim().toLowerCase();

    if (!query) {
        return divisionUsers.value;
    }

    return divisionUsers.value.filter((user) => user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query));
});

const selectedParticipants = computed(() =>
    props.users.filter((user) => form.participant_ids.includes(user.id)).map((user) => ({ id: user.id, name: user.name })),
);

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

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
        participantQuery.value = '';
    },
);
</script>

<template>
    <Head title="Buat dokumen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-4 py-6 md:px-8 md:py-8" @submit.prevent="submit">
            <PageHeader title="Buat dokumen" description="Mulai dari template notulen, proposal, laporan, brosur, atau struktur kosong.">
                <template #actions>
                    <div class="flex w-full items-center gap-2 sm:w-auto sm:justify-end">
                        <Button variant="outline" as-child class="flex-1 sm:flex-none">
                            <Link :href="route('meetings.index')">Batal</Link>
                        </Button>
                        <Button type="submit" :loading="form.processing" class="flex-1 sm:flex-none">Simpan draf</Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="min-w-0 space-y-6">
                    <section class="rounded-lg border border-border bg-card p-5 shadow-card md:p-6">
                        <div class="space-y-1.5">
                            <Label for="title">Judul dokumen</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                class="h-11 text-base"
                                placeholder="Contoh: Rapat koordinasi mingguan divisi IT"
                                :aria-invalid="Boolean(form.errors.title)"
                                aria-describedby="title-hint title-error"
                            />
                            <p id="title-hint" class="text-xs text-muted-foreground">Gunakan judul yang spesifik agar mudah ditemukan kembali.</p>
                            <p v-if="form.errors.title" id="title-error" class="text-xs font-medium text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div v-if="isMeetingDocument" class="space-y-1.5">
                                <Label for="meeting_type">Jenis rapat</Label>
                                <Select id="meeting_type" v-model="form.meeting_type">
                                    <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                                </Select>
                            </div>
                            <div v-else class="space-y-1.5">
                                <Label>Jenis dokumen</Label>
                                <div class="flex h-10 items-center rounded-md border border-border bg-muted/40 px-3 text-sm text-muted-foreground">
                                    {{ documentTypeLabels[selectedTemplate?.document_type ?? 'other'] }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="template">Template</Label>
                                <Select id="template" v-model="form.meeting_template_id">
                                    <option value="">Tanpa template</option>
                                    <optgroup v-for="group in groupedTemplates" :key="group.value" :label="group.label">
                                        <option v-for="template in group.templates" :key="template.id" :value="String(template.id)">
                                            {{ template.name }}
                                        </option>
                                    </optgroup>
                                </Select>
                            </div>
                        </div>

                        <div v-if="selectedTemplate" class="mt-5 flex gap-3 rounded-md border border-border bg-muted/30 p-4">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-md bg-card text-muted-foreground ring-1 ring-border"
                            >
                                <FileText class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                        :class="documentTypeClasses[selectedTemplate.document_type]"
                                    >
                                        {{ documentTypeLabels[selectedTemplate.document_type] ?? selectedTemplate.document_type }}
                                    </span>
                                    <p class="text-sm font-semibold">{{ selectedTemplate.name }}</p>
                                </div>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                    {{ selectedTemplate.description ?? 'Template siap digunakan.' }}
                                </p>
                                <div v-if="selectedTemplate.sections?.length" class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="section in selectedTemplate.sections"
                                        :key="section"
                                        class="rounded-full bg-card px-2.5 py-1 text-[11px] text-muted-foreground ring-1 ring-border"
                                    >
                                        {{ section }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-card md:p-6">
                        <div class="space-y-1.5">
                            <Label for="agenda">Agenda atau ringkasan</Label>
                            <textarea
                                id="agenda"
                                v-model="form.agenda"
                                rows="4"
                                class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm leading-6 text-foreground shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui placeholder:text-muted-foreground hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="Tuliskan poin agenda yang akan dibahas"
                            />
                            <p class="text-xs text-muted-foreground">Agenda membantu peserta memahami konteks sebelum membaca isi dokumen.</p>
                        </div>
                    </section>

                    <section class="space-y-2">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <Label>Isi dokumen</Label>
                            <p class="text-xs text-muted-foreground">
                                Ketik <span class="font-semibold text-foreground">@</span> untuk orang atau divisi,
                                <span class="font-semibold text-foreground">#</span> untuk topik.
                            </p>
                        </div>
                        <MeetingEditor v-model="form.content" :users="props.users" :divisions="props.divisions" :topics="props.topics" />
                    </section>
                </div>

                <aside class="min-w-0 space-y-6">
                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <h2 class="text-sm font-semibold">Detail dokumen</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Jadwal, lokasi, dan tingkat kerahasiaan.</p>

                        <div class="mt-4 space-y-4">
                            <div class="space-y-1.5">
                                <Label for="scheduled_at" class="flex items-center gap-2">
                                    <CalendarDays class="size-4 text-muted-foreground" />
                                    {{ isMeetingDocument ? 'Jadwal rapat' : 'Tanggal dokumen' }}
                                </Label>
                                <Input
                                    id="scheduled_at"
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                    :aria-invalid="Boolean(form.errors.scheduled_at)"
                                />
                                <p v-if="form.errors.scheduled_at" class="text-xs font-medium text-destructive">{{ form.errors.scheduled_at }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="division_id">Divisi penyelenggara</Label>
                                <Select id="division_id" v-model="form.division_id">
                                    <option value="">Tanpa divisi</option>
                                    <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">
                                        {{ division.name }}
                                    </option>
                                </Select>
                            </div>

                            <template v-if="isMeetingDocument">
                                <div class="space-y-1.5">
                                    <Label for="location" class="flex items-center gap-2">
                                        <MapPin class="size-4 text-muted-foreground" />
                                        Lokasi
                                    </Label>
                                    <Input id="location" v-model="form.location" placeholder="Ruang rapat atau daring" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="meeting_link" class="flex items-center gap-2">
                                        <Link2 class="size-4 text-muted-foreground" />
                                        Tautan rapat
                                    </Label>
                                    <Input
                                        id="meeting_link"
                                        v-model="form.meeting_link"
                                        placeholder="https://"
                                        :aria-invalid="Boolean(form.errors.meeting_link)"
                                    />
                                    <p v-if="form.errors.meeting_link" class="text-xs font-medium text-destructive">{{ form.errors.meeting_link }}</p>
                                </div>
                            </template>

                            <div class="space-y-1.5">
                                <Label for="confidentiality" class="flex items-center gap-2">
                                    <ShieldCheck class="size-4 text-muted-foreground" />
                                    Kerahasiaan
                                </Label>
                                <Select id="confidentiality" v-model="form.confidentiality">
                                    <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                                </Select>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 text-sm font-semibold">
                                    <Users class="size-4 text-muted-foreground" />
                                    Peserta
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        selectedDivision
                                            ? `Anggota ${selectedDivision.name} otomatis dipilih.`
                                            : 'Pilih divisi untuk memilih peserta secara otomatis.'
                                    }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground"
                                >{{ participantCount }} dipilih</span
                            >
                        </div>

                        <div v-if="selectedParticipants.length" class="mt-4 flex items-center gap-3 rounded-md border border-border bg-muted/30 p-3">
                            <AvatarGroup :people="selectedParticipants" :max="6" />
                            <p class="text-xs text-muted-foreground">Peserta terpilih</p>
                        </div>

                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <div class="relative flex-1">
                                <label for="participant-search" class="sr-only">Cari peserta</label>
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input id="participant-search" v-model="participantQuery" placeholder="Cari nama atau email" class="pl-9" />
                            </div>
                            <div class="flex gap-2">
                                <Button type="button" size="sm" variant="outline" class="h-10" @click="selectAllParticipants">Pilih semua</Button>
                                <Button type="button" size="sm" variant="ghost" class="h-10" @click="clearParticipants">Kosongkan</Button>
                            </div>
                        </div>

                        <div class="mt-3 max-h-72 space-y-1 overflow-y-auto pr-1">
                            <label
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 text-sm transition-colors duration-fast hover:bg-muted/60"
                            >
                                <input
                                    v-model="form.participant_ids"
                                    type="checkbox"
                                    :value="user.id"
                                    class="size-4 shrink-0 rounded border-input accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-[10px] font-semibold text-secondary-foreground"
                                >
                                    {{ initials(user.name) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium text-foreground">{{ user.name }}</span>
                                    <span class="block truncate text-xs text-muted-foreground">{{ user.email }}</span>
                                </span>
                            </label>
                            <p v-if="filteredUsers.length === 0" class="px-2 py-6 text-center text-xs text-muted-foreground">
                                {{ participantQuery ? 'Tidak ada peserta yang cocok.' : 'Belum ada pengguna pada divisi ini.' }}
                            </p>
                        </div>
                        <p v-if="form.errors.participant_ids" class="mt-2 text-xs font-medium text-destructive">{{ form.errors.participant_ids }}</p>
                    </section>
                </aside>
            </div>
        </form>
    </AppLayout>
</template>
