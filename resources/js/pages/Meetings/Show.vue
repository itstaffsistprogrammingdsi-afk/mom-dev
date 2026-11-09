<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { attendanceLabels, confidentialityLabels, documentTypeClasses, documentTypeLabels, formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Decision, type Division, type Meeting, type Participant, type SharedData, type Tag } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CalendarClock, CheckCircle2, Download, ExternalLink, FileUp, Lock, MapPin, Paperclip, Pencil, Plus, Share2, Sparkles, Trash2, Unlock, UserRound, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Summary {
    id: number;
    content: {
        ringkasan?: string;
        poin?: string[];
        keputusan?: string[];
        action_items?: { title: string; description?: string | null; pic_name?: string | null; division_name?: string | null; due_date?: string | null }[];
        saran_tag?: Record<string, string[]>;
    } & Record<string, unknown>;
    version: number;
    status: string;
    generation_status: string;
    error_message: string | null;
    model: string | null;
    approved_at: string | null;
}

interface Attachment {
    id: number;
    original_name: string;
    url: string | null;
    kind: string;
    mime_type: string | null;
}

interface Version {
    id: number;
    version: number;
    reason: string;
    created_at: string;
}

interface FullMeeting extends Meeting {
    participants: Participant[];
    tags: Tag[];
    decisions: Decision[];
    action_items: ActionItem[];
    summaries: Summary[];
    attachments: Attachment[];
    versions: Version[];
    approved_by?: { id: number; name: string } | null;
    template?: { id: number; name: string } | null;
    finalized_at: string | null;
}

const props = defineProps<{
    meeting: FullMeeting;
    can: { update: boolean; finalize: boolean; generateAi?: boolean; manage?: boolean };
    users?: { id: number; name: string }[];
    divisions?: Division[];
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const errors = computed(() => (page.props.errors ?? {}) as Record<string, string>);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dokumen', href: '/notulen' },
    { title: props.meeting.title, href: `/notulen/${props.meeting.id}` },
];

const aiConsent = ref(false);
const editingTaskId = ref<number | null>(null);
const shareOpen = ref(false);

const shareForm = useForm({
    recipients: '',
    message: '',
    include_pdf: true,
    confirm_share: false,
});

const taskForm = reactive({
    title: '',
    description: '',
    pic_id: '',
    division_id: '',
    due_date: '',
    status: 'new',
});

const editTaskForm = reactive({
    title: '',
    description: '',
    pic_id: '',
    division_id: '',
    due_date: '',
    status: 'new',
});

const attachmentForm = reactive<{ file: File | null; url: string; name: string }>({
    file: null,
    url: '',
    name: '',
});

const canManage = computed(() => props.can.manage ?? props.can.update);
const requiresShareConfirmation = computed(() => ['restricted', 'secret'].includes(props.meeting.confidentiality));
const taskUsers = computed(() => props.users ?? []);
const taskDivisions = computed(() => props.divisions ?? []);

const attachmentUrl = (attachment: Attachment) => route('attachments.download', attachment.id);

function finalize() {
    if (!window.confirm('Finalkan notulen ini? Isi akan dikunci untuk perubahan.')) {
        return;
    }

    router.post(route('meetings.finalize', props.meeting.id), {}, { preserveScroll: true });
}

function lockMeeting() {
    router.post(route('meetings.lock', props.meeting.id), {}, { preserveScroll: true });
}

function unlockMeeting() {
    router.post(route('meetings.unlock', props.meeting.id), {}, { preserveScroll: true });
}

function generateAi() {
    if (props.meeting.confidentiality === 'secret' && !aiConsent.value) {
        if (!window.confirm('Notulen ini rahasia. Setujui pemrosesan AI secara eksplisit?')) {
            return;
        }

        aiConsent.value = true;
    }

    router.post(route('meetings.ai.generate', props.meeting.id), { ai_consent: aiConsent.value }, { preserveScroll: true });
}

function openShare() {
    shareForm.clearErrors();
    shareOpen.value = true;
}

function submitShare() {
    shareForm.post(route('meetings.share', props.meeting.id), {
        preserveScroll: true,
        onSuccess: () => {
            shareOpen.value = false;
            shareForm.reset();
        },
    });
}

function approveSummary(summary: Summary) {
    router.post(route('ai-summaries.approve', summary.id), {}, { preserveScroll: true });
}

function rejectSummary(summary: Summary) {
    router.post(route('ai-summaries.reject', summary.id), {}, { preserveScroll: true });
}

function applySummaryActions(summary: Summary) {
    router.post(route('ai-summaries.apply', summary.id), {}, { preserveScroll: true });
}

function updateTaskStatus(task: ActionItem, status: string) {
    router.patch(route('action-items.status', task.id), { status }, { preserveScroll: true });
}

function canEditTask(task: ActionItem) {
    return canManage.value || task.pic?.id === user.value.id;
}

function addTask() {
    router.post(route('action-items.store', props.meeting.id), { ...taskForm }, {
        preserveScroll: true,
        onSuccess: () => {
            Object.assign(taskForm, { title: '', description: '', pic_id: '', division_id: '', due_date: '', status: 'new' });
        },
    });
}

function startEditTask(task: ActionItem) {
    editingTaskId.value = task.id;
    Object.assign(editTaskForm, {
        title: task.title,
        description: task.description ?? '',
        pic_id: task.pic?.id ? String(task.pic.id) : '',
        division_id: task.division?.id ? String(task.division.id) : '',
        due_date: task.due_date ? String(task.due_date).slice(0, 10) : '',
        status: task.status,
    });
}

function saveEditTask(task: ActionItem) {
    router.patch(route('action-items.update', task.id), { ...editTaskForm }, {
        preserveScroll: true,
        onSuccess: () => {
            editingTaskId.value = null;
        },
    });
}

function deleteTask(task: ActionItem) {
    if (!window.confirm(`Hapus action item "${task.title}"?`)) {
        return;
    }

    router.delete(route('action-items.destroy', task.id), { preserveScroll: true });
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    attachmentForm.file = input.files?.[0] ?? null;
}

function uploadAttachment() {
    router.post(route('attachments.store', props.meeting.id), {
        file: attachmentForm.file,
        url: attachmentForm.url || null,
        name: attachmentForm.name || null,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            attachmentForm.file = null;
            attachmentForm.url = '';
            attachmentForm.name = '';
        },
    });
}

function deleteAttachment(attachment: Attachment) {
    if (!window.confirm(`Hapus lampiran "${attachment.original_name}"?`)) {
        return;
    }

    router.delete(route('attachments.destroy', attachment.id), { preserveScroll: true });
}

function restoreVersion(version: Version) {
    if (!window.confirm(`Pulihkan versi ${version.version}? Isi saat ini akan diganti.`)) {
        return;
    }

    router.post(route('meetings.versions.restore', { meeting: props.meeting.id, version: version.id }), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="props.meeting.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader :title="props.meeting.title">
                <template #actions>
                    <Button variant="outline" @click="openShare">
                        <Share2 class="size-4" />
                        Bagikan
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                <Download class="size-4" />
                                Ekspor
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52">
                            <DropdownMenuItem as-child>
                                <a :href="route('meetings.export.pdf', props.meeting.id)" class="flex w-full items-center gap-2">
                                    <Download class="size-4" />
                                    PDF
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <a :href="route('meetings.export.word', props.meeting.id)" class="flex w-full items-center gap-2">
                                    <Download class="size-4" />
                                    Word (.docx)
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button v-if="props.can.update" as-child variant="outline">
                        <Link :href="route('meetings.edit', props.meeting.id)">Sunting</Link>
                    </Button>
                    <Button v-if="props.can.generateAi && props.meeting.status !== 'locked'" variant="outline" @click="generateAi">
                        <Sparkles class="size-4" />
                        Resume AI
                    </Button>
                    <Button v-if="props.can.finalize && props.meeting.status === 'draft'" @click="finalize">
                        {{ props.meeting.document_type === 'meeting' ? 'Finalkan notulen' : 'Finalkan dokumen' }}
                    </Button>
                    <Button v-if="user.role === 'admin' && props.meeting.status === 'final'" variant="outline" @click="lockMeeting">
                        <Lock class="size-4" />
                        Kunci
                    </Button>
                    <Button v-if="user.role === 'admin' && props.meeting.status === 'locked'" variant="outline" @click="unlockMeeting">
                        <Unlock class="size-4" />
                        Buka kunci
                    </Button>
                </template>
            </PageHeader>

            <div v-if="errors.meeting" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                {{ errors.meeting }}
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="documentTypeClasses[props.meeting.document_type]">
                    {{ documentTypeLabels[props.meeting.document_type] ?? props.meeting.document_type }}
                </span>
                <StatusBadge kind="meeting" :value="props.meeting.status" />
                <StatusBadge kind="confidentiality" :value="props.meeting.confidentiality" />
                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                    {{ meetingTypeLabels[props.meeting.meeting_type] }}
                </span>
                <span v-if="props.meeting.approved_by" class="flex items-center gap-1 text-xs text-muted-foreground">
                    <CheckCircle2 class="size-3.5" />
                    Difinalkan oleh {{ props.meeting.approved_by.name }} · {{ formatDateTime(props.meeting.finalized_at) }}
                </span>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle class="text-base">{{ props.meeting.document_type === 'meeting' ? 'Informasi rapat' : 'Informasi dokumen' }}</CardTitle></CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="flex items-center gap-2">
                            <CalendarClock class="size-4 text-muted-foreground" />
                            <span>{{ formatDateTime(props.meeting.scheduled_at) }}</span>
                        </div>
                        <div v-if="props.meeting.location" class="flex items-center gap-2">
                            <MapPin class="size-4 text-muted-foreground" />
                            <span>{{ props.meeting.location }}</span>
                        </div>
                        <div v-if="props.meeting.meeting_link" class="flex items-center gap-2">
                            <ExternalLink class="size-4 text-muted-foreground" />
                            <a :href="props.meeting.meeting_link" target="_blank" rel="noopener" class="truncate text-primary underline">
                                Tautan rapat
                            </a>
                        </div>
                        <div class="flex items-center gap-2">
                            <UserRound class="size-4 text-muted-foreground" />
                            <span>{{ props.meeting.document_type === 'meeting' ? 'Notulis' : 'Pembuat' }}: {{ props.meeting.creator?.name ?? '—' }}</span>
                        </div>
                        <p class="text-muted-foreground">Divisi: {{ props.meeting.division?.name ?? 'Tanpa divisi' }}</p>
                        <p v-if="props.meeting.template" class="text-muted-foreground">Template: {{ props.meeting.template.name }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Tag</CardTitle></CardHeader>
                    <CardContent>
                        <div v-if="props.meeting.tags.length" class="flex flex-wrap gap-1.5">
                            <Link
                                v-for="tag in props.meeting.tags"
                                :key="tag.id"
                                :href="
                                    tag.target_id
                                        ? tag.type === 'division'
                                            ? route('divisions.show-id', tag.target_id)
                                            : tag.type === 'person'
                                              ? route('people.show', tag.target_id)
                                              : route('topics.show-id', tag.target_id)
                                        : '#'
                                "
                                class="rounded-full bg-muted px-2 py-1 text-xs font-medium hover:bg-muted/70"
                            >
                                {{ tag.type === 'topic' ? '#' : '@' }}{{ tag.target_name }}
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Belum ada tag.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Peserta</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p v-if="props.meeting.participants.length === 0" class="text-muted-foreground">Belum ada peserta.</p>
                        <div v-for="participant in props.meeting.participants" :key="participant.id" class="flex items-center justify-between gap-2">
                            <Link
                                v-if="participant.user?.id"
                                :href="route('people.show', participant.user.id)"
                                class="truncate hover:text-primary hover:underline"
                            >
                                {{ participant.user.name }}
                            </Link>
                            <span v-else class="truncate">{{ participant.name ?? participant.email }}</span>
                            <span class="text-xs text-muted-foreground">{{ attendanceLabels[participant.attendance] }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-2">
                <h2 class="text-lg font-semibold">Isi dokumen</h2>
                <MeetingEditor :model-value="props.meeting.content" :editable="false" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Keputusan</CardTitle></CardHeader>
                    <CardContent>
                        <p v-if="props.meeting.decisions.length === 0" class="text-sm text-muted-foreground">Belum ada keputusan tercatat.</p>
                        <ol v-else class="list-decimal space-y-2 pl-5 text-sm">
                            <li v-for="decision in props.meeting.decisions" :key="decision.id">
                                {{ decision.content }}
                                <span v-if="decision.topic" class="ml-1 text-xs text-muted-foreground">#{{ decision.topic.name }}</span>
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-base">Resume AI</CardTitle>
                        <span class="text-xs text-muted-foreground">Dibuat AI · perlu verifikasi</span>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <p v-if="props.meeting.summaries.length === 0" class="text-muted-foreground">
                            Belum ada resume. Klik "Resume AI" untuk membuat ringkasan.
                        </p>
                        <div v-for="summary in props.meeting.summaries" :key="summary.id" class="space-y-3 rounded-lg border p-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <Sparkles class="size-3.5" />
                                <span>v{{ summary.version }} · {{ summary.status }} · {{ summary.generation_status }}</span>
                                <span v-if="summary.model">· {{ summary.model }}</span>
                            </div>

                            <p v-if="summary.generation_status === 'queued' || summary.generation_status === 'processing'" class="text-muted-foreground">
                                Resume sedang diproses…
                            </p>
                            <p v-if="summary.generation_status === 'failed'" class="text-rose-600">
                                Gagal membuat resume: {{ summary.error_message ?? 'Layanan AI tidak tersedia.' }}
                            </p>

                            <p v-if="summary.content.ringkasan">{{ summary.content.ringkasan }}</p>
                            <ul v-if="summary.content.poin?.length" class="list-disc space-y-1 pl-5">
                                <li v-for="(point, index) in summary.content.poin" :key="index">{{ point }}</li>
                            </ul>
                            <div v-if="summary.content.keputusan?.length">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Keputusan</p>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li v-for="(decision, index) in summary.content.keputusan" :key="index">{{ decision }}</li>
                                </ul>
                            </div>
                            <div v-if="summary.content.action_items?.length">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Usulan action item</p>
                                <ul class="list-disc space-y-1 pl-5">
                                    <li v-for="(item, index) in summary.content.action_items" :key="index">{{ item.title }}</li>
                                </ul>
                            </div>

                            <div v-if="summary.status === 'draft' && summary.generation_status === 'completed' && canManage" class="flex flex-wrap gap-2">
                                <Button size="sm" @click="approveSummary(summary)">Setujui</Button>
                                <Button size="sm" variant="outline" @click="rejectSummary(summary)">Tolak</Button>
                                <Button v-if="summary.content.action_items?.length" size="sm" variant="outline" @click="applySummaryActions(summary)">
                                    Terapkan action item
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-base">Action item</CardTitle>
                    <span class="text-xs text-muted-foreground">PIC dan tenggat wajib sebelum finalisasi</span>
                </CardHeader>
                <CardContent class="space-y-4">
                    <form v-if="canManage && props.meeting.status === 'draft'" class="grid gap-2 rounded-lg border bg-muted/20 p-3 md:grid-cols-6" @submit.prevent="addTask">
                        <input v-model="taskForm.title" required placeholder="Tugas baru…" class="h-9 rounded-md border bg-background px-3 text-sm md:col-span-2" />
                        <select v-model="taskForm.pic_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">PIC…</option>
                            <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">{{ person.name }}</option>
                        </select>
                        <select v-model="taskForm.division_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">Divisi…</option>
                            <option v-for="division in taskDivisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                        </select>
                        <input v-model="taskForm.due_date" type="date" class="h-9 rounded-md border bg-background px-3 text-sm" />
                        <Button type="submit" class="h-9">
                            <Plus class="size-4" />
                            Tambah
                        </Button>
                    </form>

                    <p v-if="props.meeting.action_items.length === 0" class="text-sm text-muted-foreground">Belum ada action item.</p>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="py-2 pr-3">Tugas</th>
                                    <th class="py-2 pr-3">PIC</th>
                                    <th class="py-2 pr-3">Tenggat</th>
                                    <th class="py-2 pr-3">Status</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="task in props.meeting.action_items" :key="task.id">
                                    <td class="py-2 pr-3">
                                        <template v-if="editingTaskId === task.id">
                                            <input v-model="editTaskForm.title" class="w-full rounded-md border bg-background px-2 py-1 text-sm" />
                                        </template>
                                        <template v-else>
                                            <p class="font-medium">{{ task.title }}</p>
                                            <p v-if="task.description" class="text-xs text-muted-foreground">{{ task.description }}</p>
                                        </template>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <select v-if="editingTaskId === task.id" v-model="editTaskForm.pic_id" class="h-8 rounded-md border bg-background px-2 text-xs">
                                            <option value="">—</option>
                                            <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">{{ person.name }}</option>
                                        </select>
                                        <span v-else>{{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? '—' }}</span>
                                    </td>
                                    <td class="py-2 pr-3" :class="{ 'font-medium text-rose-600': task.is_overdue }">
                                        <input v-if="editingTaskId === task.id" v-model="editTaskForm.due_date" type="date" class="h-8 rounded-md border bg-background px-2 text-xs" />
                                        <span v-else>{{ formatDate(task.due_date) }}</span>
                                    </td>
                                    <td class="py-2 pr-3">
                                        <select
                                            v-if="canEditTask(task) && editingTaskId !== task.id"
                                            :value="task.status"
                                            class="h-8 rounded-md border bg-background px-2 text-xs"
                                            @change="updateTaskStatus(task, ($event.target as HTMLSelectElement).value)"
                                        >
                                            <option value="new">Baru</option>
                                            <option value="in_progress">Berjalan</option>
                                            <option value="done">Selesai</option>
                                            <option value="cancelled">Dibatalkan</option>
                                        </select>
                                        <select v-else-if="editingTaskId === task.id" v-model="editTaskForm.status" class="h-8 rounded-md border bg-background px-2 text-xs">
                                            <option value="new">Baru</option>
                                            <option value="in_progress">Berjalan</option>
                                            <option value="done">Selesai</option>
                                            <option value="cancelled">Dibatalkan</option>
                                        </select>
                                        <StatusBadge v-else kind="action" :value="task.status" />
                                    </td>
                                    <td class="py-2">
                                        <div v-if="canManage" class="flex items-center gap-1">
                                            <template v-if="editingTaskId === task.id">
                                                <Button size="sm" @click="saveEditTask(task)">Simpan</Button>
                                                <Button size="sm" variant="ghost" @click="editingTaskId = null"><X class="size-4" /></Button>
                                            </template>
                                            <template v-else>
                                                <Button size="sm" variant="ghost" @click="startEditTask(task)"><Pencil class="size-4" /></Button>
                                                <Button size="sm" variant="ghost" @click="deleteTask(task)"><Trash2 class="size-4" /></Button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Lampiran</CardTitle></CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <form v-if="canManage" class="space-y-2 rounded-lg border bg-muted/20 p-3" @submit.prevent="uploadAttachment">
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="file" class="text-xs" @change="onFileChange" />
                                <span class="text-xs text-muted-foreground">atau</span>
                                <input v-model="attachmentForm.url" type="url" placeholder="https://…" class="h-8 flex-1 rounded-md border bg-background px-2 text-xs" />
                            </div>
                            <div class="flex gap-2">
                                <input v-model="attachmentForm.name" placeholder="Nama lampiran (opsional)" class="h-8 flex-1 rounded-md border bg-background px-2 text-xs" />
                                <Button type="submit" size="sm">
                                    <FileUp class="size-4" />
                                    Unggah
                                </Button>
                            </div>
                        </form>

                        <p v-if="props.meeting.attachments.length === 0" class="text-muted-foreground">Belum ada lampiran.</p>
                        <div v-for="attachment in props.meeting.attachments" :key="attachment.id" class="flex items-center justify-between gap-2 rounded-md border p-2">
                            <a :href="attachmentUrl(attachment)" target="_blank" rel="noopener" class="flex min-w-0 items-center gap-2 hover:text-primary">
                                <Paperclip class="size-4 shrink-0 text-muted-foreground" />
                                <span class="truncate">{{ attachment.original_name }}</span>
                            </a>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-muted-foreground">{{ attachment.kind }}</span>
                                <Button v-if="canManage" size="sm" variant="ghost" @click="deleteAttachment(attachment)">
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Riwayat versi</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p v-if="props.meeting.versions.length === 0" class="text-muted-foreground">Belum ada snapshot versi.</p>
                        <div v-for="version in props.meeting.versions" :key="version.id" class="flex items-center justify-between gap-2 rounded-md border p-2">
                            <div>
                                <p class="font-medium">Versi {{ version.version }}</p>
                                <p class="text-xs text-muted-foreground">{{ version.reason }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted-foreground">{{ formatDateTime(version.created_at) }}</span>
                                <Button v-if="canManage" size="sm" variant="outline" @click="restoreVersion(version)">Pulihkan</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="shareOpen">
            <DialogContent class="sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submitShare">
                    <DialogHeader>
                        <DialogTitle>Bagikan dokumen</DialogTitle>
                        <DialogDescription>
                            Kirim tautan dan, jika dipilih, salinan PDF ke alamat email penerima.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-2">
                        <label for="share-recipients" class="text-sm font-medium">Email penerima</label>
                        <textarea
                            id="share-recipients"
                            v-model="shareForm.recipients"
                            rows="2"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="nama@perusahaan.com, rekan@perusahaan.com"
                        />
                        <p class="text-xs text-muted-foreground">Pisahkan dengan koma. Maksimal 10 penerima.</p>
                        <p v-if="shareForm.errors.recipients" class="text-xs text-rose-600">{{ shareForm.errors.recipients }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="share-message" class="text-sm font-medium">Pesan (opsional)</label>
                        <textarea
                            id="share-message"
                            v-model="shareForm.message"
                            rows="3"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Tambahkan catatan untuk penerima…"
                        />
                        <p v-if="shareForm.errors.message" class="text-xs text-rose-600">{{ shareForm.errors.message }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="shareForm.include_pdf" type="checkbox" class="size-4 rounded border" />
                        Lampirkan salinan PDF
                    </label>

                    <div v-if="requiresShareConfirmation" class="space-y-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <p>
                            Dokumen ini berlabel <strong>{{ confidentialityLabels[props.meeting.confidentiality] }}</strong>.
                            Pastikan seluruh penerima berhak menerima dokumen.
                        </p>
                        <label class="flex items-center gap-2">
                            <input v-model="shareForm.confirm_share" type="checkbox" class="size-4 rounded border" />
                            Saya mengonfirmasi penerima berhak.
                        </label>
                        <p v-if="shareForm.errors.confirm_share" class="text-rose-600">{{ shareForm.errors.confirm_share }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="shareOpen = false">Batal</Button>
                        <Button type="submit" :disabled="shareForm.processing">
                            <Share2 class="size-4" />
                            Kirim email
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
