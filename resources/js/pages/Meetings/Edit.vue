<script setup lang="ts">
import AvatarGroup from '@/components/AvatarGroup.vue';
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import TagChip from '@/components/TagChip.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { attendanceLabels, confidentialityLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting, type Participant, type SharedData, type Tag } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { AlertCircle, CheckCircle2, CircleDashed, Loader2, Users, Wifi } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface EditableMeeting extends Meeting {
    participants: Participant[];
    tags: Tag[];
    division?: Division | null;
}

const props = defineProps<{
    meeting: EditableMeeting;
    users: { id: number; name: string }[];
    divisions: Division[];
    topics: { id: number; name: string }[];
    collab?: { url: string; document: string; token: string; user: string } | null;
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const collabUrl = props.collab?.url ?? null;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dokumen', href: '/notulen' },
    { title: props.meeting.title, href: `/notulen/${props.meeting.id}` },
    { title: 'Sunting', href: `/notulen/${props.meeting.id}/sunting` },
];

const form = useForm({
    title: props.meeting.title,
    agenda: props.meeting.agenda ?? '',
    confidentiality: props.meeting.confidentiality,
    content: (props.meeting.content ?? { type: 'doc', content: [{ type: 'paragraph' }] }) as JSONContent,
});

const autosaveState = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const lastSavedAt = ref<string | null>(null);
let autosaveTimer: ReturnType<typeof setTimeout> | null = null;
let autosaveReady = false;

const autosaveLabel = computed(() => {
    if (autosaveState.value === 'saving') {
        return 'Menyimpan…';
    }

    if (autosaveState.value === 'saved') {
        return `Tersimpan ${lastSavedAt.value ?? ''}`.trim();
    }

    if (autosaveState.value === 'error') {
        return 'Gagal menyimpan otomatis';
    }

    return 'Autosave aktif';
});

const autosaveTone = computed(() => {
    if (autosaveState.value === 'error') {
        return 'border-destructive/30 bg-destructive/10 text-destructive';
    }

    if (autosaveState.value === 'saved') {
        return 'border-success/30 bg-success/10 text-success';
    }

    return 'border-border bg-muted/40 text-muted-foreground';
});

const collabActive = computed(() => Boolean(props.collab));
const participants = computed(() =>
    props.meeting.participants.map((participant) => ({
        id: participant.id,
        name: participant.user?.name ?? participant.name ?? participant.email ?? 'Tanpa nama',
        attendance: participant.attendance,
        email: participant.user?.email ?? participant.email ?? null,
    })),
);

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function tagHref(tag: Tag): string | undefined {
    if (!tag.target_id) {
        return undefined;
    }

    if (tag.type === 'division') {
        return route('divisions.show-id', tag.target_id);
    }

    if (tag.type === 'person') {
        return route('people.show', tag.target_id);
    }

    return route('topics.show-id', tag.target_id);
}

onMounted(() => {
    window.setTimeout(() => {
        autosaveReady = true;
    }, 800);
});

watch(
    () => [form.title, form.agenda, form.confidentiality, form.content],
    () => {
        if (!autosaveReady) {
            return;
        }

        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
        }

        autosaveTimer = setTimeout(runAutosave, 1500);
    },
    { deep: true },
);

async function runAutosave() {
    if (form.processing || props.meeting.status !== 'draft') {
        return;
    }

    autosaveState.value = 'saving';

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        const response = await fetch(route('meetings.autosave', props.meeting.id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                title: form.title,
                agenda: form.agenda,
                confidentiality: form.confidentiality,
                content: form.content,
            }),
        });

        if (!response.ok) {
            throw new Error(await response.text());
        }

        const data = await response.json();
        lastSavedAt.value = new Date(data.saved_at).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
        autosaveState.value = 'saved';
    } catch {
        autosaveState.value = 'error';
    }
}

onBeforeUnmount(() => {
    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
    }
});

function submit() {
    if (autosaveTimer) {
        clearTimeout(autosaveTimer);
    }

    form.patch(route('meetings.update', props.meeting.id));
}
</script>

<template>
    <Head :title="`Sunting ${props.meeting.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <form class="mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-4 py-6 md:px-8 md:py-8" @submit.prevent="submit">
            <PageHeader
                :title="props.meeting.document_type === 'meeting' ? 'Sunting notulen' : 'Sunting dokumen'"
                description="Perubahan tercatat di audit log. Dokumen final hanya dapat diubah oleh admin."
            >
                <template #actions>
                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
                        <span
                            class="inline-flex basis-full items-center justify-center gap-2 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors duration-fast sm:basis-auto sm:justify-start"
                            :class="autosaveTone"
                            role="status"
                            aria-live="polite"
                        >
                            <Loader2 v-if="autosaveState === 'saving'" class="size-3.5 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                            <CheckCircle2 v-else-if="autosaveState === 'saved'" class="size-3.5" aria-hidden="true" />
                            <AlertCircle v-else-if="autosaveState === 'error'" class="size-3.5" aria-hidden="true" />
                            <CircleDashed v-else class="size-3.5" aria-hidden="true" />
                            {{ autosaveLabel }}
                        </span>
                        <Button variant="outline" as-child class="flex-1 sm:flex-none">
                            <Link :href="route('meetings.show', props.meeting.id)">Batal</Link>
                        </Button>
                        <Button type="submit" :loading="form.processing" class="flex-1 sm:flex-none">Simpan perubahan</Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0 space-y-6">
                    <section class="rounded-lg border border-border bg-card p-5 shadow-card md:p-6">
                        <div class="space-y-1.5">
                            <Label for="title">Judul</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                class="h-11 text-base font-semibold"
                                :aria-invalid="Boolean(form.errors.title)"
                                aria-describedby="edit-title-error"
                            />
                            <p v-if="form.errors.title" id="edit-title-error" class="text-xs font-medium text-destructive">{{ form.errors.title }}</p>
                        </div>

                        <div class="mt-4 space-y-1.5">
                            <Label for="agenda">Agenda</Label>
                            <textarea
                                id="agenda"
                                v-model="form.agenda"
                                rows="3"
                                class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm leading-6 text-foreground shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui placeholder:text-muted-foreground hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="Agenda rapat"
                            />
                        </div>
                    </section>

                    <section class="space-y-2">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <Label>Isi dokumen</Label>
                            <p class="text-xs text-muted-foreground">Autosave berjalan setiap 1,5 detik saat dokumen berstatus draf.</p>
                        </div>
                        <MeetingEditor
                            v-model="form.content"
                            :users="props.users"
                            :divisions="props.divisions"
                            :topics="props.topics"
                            :collab-url="collabUrl"
                            :collab-document="props.collab?.document ?? `meeting-${props.meeting.id}`"
                            :collab-token="props.collab?.token ?? null"
                            :collab-user="props.collab?.user ?? user.name"
                        />
                    </section>
                </div>

                <aside class="min-w-0 space-y-6">
                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <h2 class="text-sm font-semibold">Pengaturan</h2>
                        <div class="mt-4 space-y-1.5">
                            <Label for="confidentiality">Kerahasiaan</Label>
                            <Select id="confidentiality" v-model="form.confidentiality">
                                <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                            </Select>
                        </div>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <h2 class="flex items-center gap-2 text-sm font-semibold">
                            <Wifi class="size-4 text-muted-foreground" />
                            Kolaborasi
                        </h2>
                        <div class="mt-3 flex items-start gap-3">
                            <span
                                class="mt-1.5 size-2 shrink-0 rounded-full"
                                :class="collabActive ? 'bg-success' : 'bg-muted-foreground/50'"
                                aria-hidden="true"
                            ></span>
                            <div>
                                <p class="text-sm font-medium">{{ collabActive ? 'Kolaborasi aktif' : 'Mode tunggal' }}</p>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">
                                    {{
                                        collabActive
                                            ? 'Perubahan dari rekan tim muncul langsung di editor.'
                                            : 'Layanan kolaborasi tidak aktif. Perubahan hanya tersimpan dari perangkat ini.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <h2 class="text-sm font-semibold">Tag aktif</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Tag disinkronkan otomatis dari mention di isi dokumen saat disimpan.</p>
                        <div v-if="props.meeting.tags.length" class="mt-3 flex flex-wrap gap-1.5">
                            <TagChip
                                v-for="tag in props.meeting.tags"
                                :key="tag.id"
                                :kind="tag.type"
                                :label="tag.target_name ?? 'Tanpa nama'"
                                :href="tagHref(tag)"
                            />
                        </div>
                        <p v-else class="mt-3 text-sm text-muted-foreground">Belum ada tag.</p>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold">
                                <Users class="size-4 text-muted-foreground" />
                                Peserta
                            </h2>
                            <span class="text-xs text-muted-foreground">{{ participants.length }}</span>
                        </div>
                        <div v-if="participants.length" class="mt-3 space-y-3">
                            <AvatarGroup :people="participants" :max="6" />
                            <ul class="space-y-2">
                                <li v-for="participant in participants" :key="participant.id" class="flex items-center gap-2 text-sm">
                                    <span
                                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-secondary text-[10px] font-semibold text-secondary-foreground"
                                    >
                                        {{ initials(participant.name) }}
                                    </span>
                                    <span class="min-w-0 flex-1 truncate">{{ participant.name }}</span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ attendanceLabels[participant.attendance] }}</span>
                                </li>
                            </ul>
                        </div>
                        <p v-else class="mt-3 text-sm text-muted-foreground">Belum ada peserta.</p>
                    </section>
                </aside>
            </div>
        </form>
    </AppLayout>
</template>
