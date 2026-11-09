<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting, type Participant, type SharedData, type Tag } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
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
    { title: 'Notulen', href: '/notulen' },
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
        return `Tersimpan ${lastSavedAt.value ?? ''}`;
    }

    if (autosaveState.value === 'error') {
        return 'Gagal menyimpan otomatis';
    }

    return 'Autosave aktif';
});

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
        <form class="flex flex-col gap-6 p-4" @submit.prevent="submit">
            <PageHeader title="Sunting notulen" description="Perubahan akan tercatat di audit log. Notulen final hanya dapat diubah oleh admin.">
                <template #actions>
                    <span class="text-xs" :class="autosaveState === 'error' ? 'text-rose-600' : 'text-muted-foreground'">{{ autosaveLabel }}</span>
                    <Button variant="outline" as-child>
                        <Link :href="route('meetings.show', props.meeting.id)">Batal</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">Simpan perubahan</Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-4">
                <div class="space-y-4 lg:col-span-3">
                    <div class="space-y-2">
                        <Label for="title">Judul</Label>
                        <Input id="title" v-model="form.title" />
                        <p v-if="form.errors.title" class="text-xs text-rose-600">{{ form.errors.title }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="agenda">Agenda</Label>
                        <textarea id="agenda" v-model="form.agenda" rows="3" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="space-y-2">
                        <Label>Isi notulen</Label>
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
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2 rounded-lg border bg-card p-4">
                        <Label for="confidentiality">Kerahasiaan</Label>
                        <select id="confidentiality" v-model="form.confidentiality" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                            <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>

                    <div class="space-y-2 rounded-lg border bg-card p-4">
                        <p class="text-sm font-medium">Tag aktif</p>
                        <div v-if="props.meeting.tags.length" class="flex flex-wrap gap-1.5">
                            <span v-for="tag in props.meeting.tags" :key="tag.id" class="rounded-full bg-muted px-2 py-1 text-xs">
                                {{ tag.type === 'topic' ? '#' : '@' }}{{ tag.target_name }}
                            </span>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Belum ada tag.</p>
                        <p class="text-xs text-muted-foreground">Tag disinkronkan otomatis dari mention di dalam isi notulen saat disimpan.</p>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
