<script setup lang="ts">
import EditorSkeleton from '@/components/editor/EditorSkeleton.vue';
import PageHeader from '@/components/PageHeader.vue';
import TagChip from '@/components/TagChip.vue';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type Meeting, type Participant, type SharedData, type Tag } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import type { JSONContent } from '@tiptap/core';
import { AlertCircle, CheckCircle2, Cloud, Loader2 } from 'lucide-vue-next';
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref, watch, type Component } from 'vue';

const MeetingEditor = defineAsyncComponent({
    loader: () => import('@/components/MeetingEditor.vue'),
    loadingComponent: EditorSkeleton,
    delay: 120,
});

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

const autosaveBadge = computed<{ variant: BadgeVariants['variant']; label: string; icon: Component }>(() => {
    if (autosaveState.value === 'saving') {
        return { variant: 'info', label: 'Menyimpan…', icon: Loader2 };
    }

    if (autosaveState.value === 'saved') {
        return { variant: 'success', label: `Tersimpan ${lastSavedAt.value ?? ''}`, icon: CheckCircle2 };
    }

    if (autosaveState.value === 'error') {
        return { variant: 'danger', label: 'Gagal menyimpan otomatis', icon: AlertCircle };
    }

    return { variant: 'neutral', label: 'Autosave aktif', icon: Cloud };
});

function tagHref(tag: Tag): string | null {
    if (!tag.target_id) {
        return null;
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
        <form class="flex flex-col gap-section p-page pb-28 sm:pb-0 lg:p-page-lg" @submit.prevent="submit">
            <PageHeader
                :title="props.meeting.document_type === 'meeting' ? 'Sunting notulen' : 'Sunting dokumen'"
                description="Perubahan akan tercatat di audit log. Dokumen final hanya dapat diubah oleh admin."
            >
                <template #actions>
                    <div class="hidden w-full flex-wrap items-center gap-2 sm:flex sm:w-auto">
                        <Badge :variant="autosaveBadge.variant" role="status" aria-live="polite">
                            <component
                                :is="autosaveBadge.icon"
                                class="size-3.5"
                                :class="{ 'animate-spin': autosaveState === 'saving' }"
                                aria-hidden="true"
                            />
                            {{ autosaveBadge.label }}
                        </Badge>
                        <Button variant="outline" as-child>
                            <Link :href="route('meetings.show', props.meeting.id)">Batal</Link>
                        </Button>
                        <Button type="submit" :loading="form.processing">Simpan perubahan</Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
                <div class="min-w-0 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi dokumen</CardTitle>
                            <CardDescription>Judul dan agenda tampil di daftar serta halaman detail.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label for="title">Judul</Label>
                                <Input id="title" v-model="form.title" :aria-invalid="Boolean(form.errors.title)" />
                                <p v-if="form.errors.title" class="text-xs font-medium text-destructive-strong">{{ form.errors.title }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="agenda">Agenda</Label>
                                <Textarea id="agenda" v-model="form.agenda" rows="3" placeholder="Agenda atau ringkasan rapat" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-start justify-between space-y-0">
                            <div>
                                <CardTitle>Isi dokumen</CardTitle>
                                <CardDescription class="mt-1">Gunakan toolbar untuk format, tag, dan dikte suara.</CardDescription>
                            </div>
                            <Badge :variant="props.collab ? 'info' : 'neutral'">
                                {{ props.collab ? 'Kolaborasi aktif' : 'Mode tunggal' }}
                            </Badge>
                        </CardHeader>
                        <CardContent>
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
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-20">
                    <Card>
                        <CardHeader>
                            <CardTitle>Kerahasiaan</CardTitle>
                            <CardDescription>Menentukan siapa yang dapat membuka dokumen.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-1.5">
                                <Label for="confidentiality">Tingkat akses</Label>
                                <Select id="confidentiality" v-model="form.confidentiality">
                                    <option v-for="(label, value) in confidentialityLabels" :key="value" :value="value">{{ label }}</option>
                                </Select>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tag aktif</CardTitle>
                            <CardDescription>Disinkronkan otomatis dari mention di isi dokumen saat disimpan.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.meeting.tags.length" class="flex flex-wrap gap-1.5">
                                <TagChip
                                    v-for="tag in props.meeting.tags"
                                    :key="tag.id"
                                    :type="tag.type"
                                    :label="tag.target_name ?? ''"
                                    :href="tagHref(tag)"
                                />
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Belum ada tag. Ketik @ atau # di editor untuk menambahkan.</p>
                        </CardContent>
                    </Card>
                </aside>
            </div>

            <div
                class="fixed inset-x-0 bottom-0 z-40 flex flex-wrap items-center justify-between gap-2 border-t border-border/70 bg-background p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:hidden"
            >
                <Badge :variant="autosaveBadge.variant" role="status" aria-live="polite" class="text-xs">
                    <component :is="autosaveBadge.icon" class="size-3.5" :class="{ 'animate-spin': autosaveState === 'saving' }" aria-hidden="true" />
                    {{ autosaveBadge.label }}
                </Badge>
                <div class="flex shrink-0 gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('meetings.show', props.meeting.id)">Batal</Link>
                    </Button>
                    <Button type="submit" :loading="form.processing">Simpan</Button>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
