<script setup lang="ts">
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import EditorSkeleton from '@/components/editor/EditorSkeleton.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels, documentTypeLabels, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { CalendarDays, Check, Users } from 'lucide-vue-next';
import { computed, defineAsyncComponent, watch } from 'vue';

const MeetingEditor = defineAsyncComponent({
    loader: () => import('@/components/MeetingEditor.vue'),
    loadingComponent: EditorSkeleton,
    delay: 120,
});

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

const { getInitials } = useInitials();

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

function userDivisionName(divisionId: number | null): string {
    return props.divisions.find((division) => division.id === divisionId)?.name ?? 'Tanpa divisi';
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
    },
);
</script>

<template>
    <Head title="Buat dokumen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="flex flex-col gap-section p-page pb-28 sm:pb-0 lg:p-page-lg" @submit.prevent="submit">
            <PageHeader title="Buat dokumen" description="Mulai dari template notulen, proposal, laporan, brosur, atau struktur kosong.">
                <template #actions>
                    <div class="hidden w-full flex-wrap items-center gap-2 sm:flex sm:w-auto">
                        <Button variant="outline" as-child>
                            <Link :href="route('meetings.index')">Batal</Link>
                        </Button>
                        <Button type="submit" :loading="form.processing">Simpan draf</Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
                <div class="min-w-0 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi utama</CardTitle>
                            <CardDescription>Tentukan judul, jenis dokumen, dan template yang dipakai.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label for="title">Judul dokumen</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Contoh: Rapat koordinasi mingguan divisi IT"
                                    :aria-invalid="Boolean(form.errors.title)"
                                />
                                <p v-if="form.errors.title" class="text-xs font-medium text-destructive-strong">{{ form.errors.title }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div v-if="isMeetingDocument" class="space-y-1.5">
                                    <Label for="meeting_type">Jenis rapat</Label>
                                    <Select id="meeting_type" v-model="form.meeting_type">
                                        <option v-for="(label, value) in meetingTypeLabels" :key="value" :value="value">{{ label }}</option>
                                    </Select>
                                </div>
                                <div v-else class="space-y-1.5">
                                    <Label>Jenis dokumen</Label>
                                    <div class="flex h-10 items-center rounded-md border border-border bg-muted/40 px-3">
                                        <DocumentTypeBadge :type="selectedTemplate?.document_type ?? 'other'" />
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

                            <div v-if="selectedTemplate" class="rounded-md border border-border/70 bg-muted/30 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <DocumentTypeBadge :type="selectedTemplate.document_type" />
                                    <span class="text-sm font-medium">{{ selectedTemplate.name }}</span>
                                </div>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                    {{ selectedTemplate.description ?? 'Template siap digunakan.' }}
                                </p>
                                <div v-if="selectedTemplate.sections?.length" class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge v-for="section in selectedTemplate.sections" :key="section" variant="outline">
                                        {{ section }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="agenda">Agenda atau ringkasan</Label>
                                <Textarea id="agenda" v-model="form.agenda" rows="3" placeholder="Poin agenda yang dibahas" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Isi dokumen</CardTitle>
                            <CardDescription>Gunakan toolbar untuk format, tag, tabel, dan dikte suara.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <MeetingEditor v-model="form.content" :users="props.users" :divisions="props.divisions" :topics="props.topics" />
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-20">
                    <Card>
                        <CardHeader>
                            <CardTitle>Jadwal dan akses</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label for="scheduled_at">{{ isMeetingDocument ? 'Jadwal' : 'Tanggal dokumen' }}</Label>
                                <Input
                                    id="scheduled_at"
                                    v-model="form.scheduled_at"
                                    type="datetime-local"
                                    :aria-invalid="Boolean(form.errors.scheduled_at)"
                                />
                                <p v-if="form.errors.scheduled_at" class="text-xs font-medium text-destructive-strong">
                                    {{ form.errors.scheduled_at }}
                                </p>
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
                                    <Label for="location">Lokasi</Label>
                                    <Input id="location" v-model="form.location" placeholder="Ruang rapat / daring" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label for="meeting_link">Tautan rapat</Label>
                                    <Input
                                        id="meeting_link"
                                        v-model="form.meeting_link"
                                        placeholder="https://…"
                                        :aria-invalid="Boolean(form.errors.meeting_link)"
                                    />
                                    <p v-if="form.errors.meeting_link" class="text-xs font-medium text-destructive-strong">
                                        {{ form.errors.meeting_link }}
                                    </p>
                                </div>
                            </template>

                            <div class="space-y-1.5">
                                <Label for="confidentiality">Kerahasiaan</Label>
                                <Select id="confidentiality" v-model="form.confidentiality">
                                    <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Peserta</CardTitle>
                                <CardDescription class="mt-1">Pilih orang yang terlibat.</CardDescription>
                            </div>
                            <Badge variant="neutral">{{ participantCount }} dipilih</Badge>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="flex items-start gap-2 rounded-md bg-muted/40 p-2.5 text-xs leading-relaxed text-muted-foreground">
                                <Users class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                                {{
                                    selectedDivision
                                        ? `Anggota ${selectedDivision.name} otomatis dipilih.`
                                        : 'Pilih divisi untuk memilih peserta secara otomatis.'
                                }}
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <Button type="button" size="sm" variant="outline" @click="selectAllParticipants">
                                    <Check class="size-3.5" aria-hidden="true" />
                                    Pilih semua
                                </Button>
                                <Button type="button" size="sm" variant="ghost" @click="clearParticipants">Kosongkan</Button>
                            </div>

                            <div class="max-h-80 space-y-1 overflow-y-auto pr-1">
                                <label
                                    v-for="user in divisionUsers"
                                    :key="user.id"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-md border border-transparent px-2 py-1.5 text-sm transition-colors hover:border-border hover:bg-muted/40"
                                >
                                    <input
                                        v-model="form.participant_ids"
                                        type="checkbox"
                                        :value="user.id"
                                        class="size-4 rounded border-input text-primary accent-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                                    />
                                    <Avatar size="xs">
                                        <AvatarFallback class="bg-secondary text-secondary-foreground">{{ getInitials(user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium">{{ user.name }}</span>
                                        <span class="block truncate text-xs text-muted-foreground">{{ userDivisionName(user.division_id) }}</span>
                                    </span>
                                </label>
                                <p v-if="divisionUsers.length === 0" class="py-4 text-center text-xs text-muted-foreground">
                                    Belum ada pengguna pada divisi ini.
                                </p>
                            </div>

                            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CalendarDays class="size-3.5" aria-hidden="true" />
                                Peserta dapat diubah lagi setelah dokumen dibuat.
                            </p>
                        </CardContent>
                    </Card>
                </aside>
            </div>

            <div
                class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-border/70 bg-background p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:hidden"
            >
                <p class="text-xs text-muted-foreground">Draf belum disimpan</p>
                <div class="flex shrink-0 gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('meetings.index')">Batal</Link>
                    </Button>
                    <Button type="submit" :loading="form.processing">Simpan draf</Button>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
