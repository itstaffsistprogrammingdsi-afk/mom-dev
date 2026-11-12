<script setup lang="ts">
import AvatarGroup from '@/components/AvatarGroup.vue';
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TagChip from '@/components/TagChip.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    attendanceLabels,
    confidentialityLabels,
    documentTypeClasses,
    documentTypeLabels,
    formatDate,
    formatDateTime,
    meetingTypeLabels,
} from '@/lib/labels';
import {
    type ActionItem,
    type BreadcrumbItem,
    type Decision,
    type Division,
    type Meeting,
    type Participant,
    type SharedData,
    type Tag,
} from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CalendarClock,
    Check,
    CheckCircle2,
    Copy,
    Download,
    ExternalLink,
    FileText,
    FileUp,
    History,
    Info,
    ListChecks,
    Loader2,
    Lock,
    MapPin,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Plus,
    Share2,
    Sparkles,
    Tags,
    Trash2,
    Unlock,
    UserRound,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

interface Summary {
    id: number;
    content: {
        ringkasan?: string;
        poin?: string[];
        keputusan?: string[];
        action_items?: {
            title: string;
            description?: string | null;
            pic_name?: string | null;
            division_name?: string | null;
            due_date?: string | null;
        }[];
        saran_tag?: Record<string, string[]>;
    } & Record<string, unknown>;
    version: number;
    status: string;
    generation_status: string;
    error_message: string | null;
    model: string | null;
    approved_at: string | null;
}

type AiTier = 'auto' | 'expert';
type ConfirmTone = 'default' | 'danger';

const summaryModelLabels: Record<string, string> = {
    'ocg/deepseek-v4.1-flash': 'DeepSeek 4.1 Flash · Auto',
    'cx/gpt-6.1-sol': 'GPT-6.1 Sol · Expert',
};

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
    can: { update: boolean; finalize: boolean; generateAi?: boolean; manage?: boolean; share?: boolean };
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
const copiedSummaryId = ref<number | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

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
const attachmentInput = ref<HTMLInputElement | null>(null);

const confirmState = reactive<{
    open: boolean;
    title: string;
    description: string;
    label: string;
    tone: ConfirmTone;
    action: (() => void) | null;
}>({
    open: false,
    title: '',
    description: '',
    label: 'Lanjutkan',
    tone: 'default',
    action: null,
});

const canManage = computed(() => props.can.manage ?? props.can.update);
const requiresShareConfirmation = computed(() => ['restricted', 'secret'].includes(props.meeting.confidentiality));
const taskUsers = computed(() => props.users ?? []);
const taskDivisions = computed(() => props.divisions ?? []);
const canGenerate = computed(() => Boolean(props.can.generateAi) && props.meeting.status !== 'locked');

const summaryStatusLabels: Record<string, string> = {
    draft: 'Draf',
    approved: 'Disetujui',
    rejected: 'Ditolak',
};

const summaryGenerationLabels: Record<string, string> = {
    queued: 'Menunggu',
    processing: 'Diproses',
    completed: 'Selesai',
    failed: 'Gagal',
};

const aiSummaries = ref<Summary[]>([...props.meeting.summaries]);
const aiPollAttempts = ref(0);
let aiPollTimer: ReturnType<typeof setInterval> | null = null;

const hasPendingAiSummary = computed(() => aiSummaries.value.some((summary) => ['queued', 'processing'].includes(summary.generation_status)));
const doneTaskCount = computed(() => props.meeting.action_items.filter((task) => task.status === 'done').length);
const taskProgress = computed(() => {
    if (props.meeting.action_items.length === 0) {
        return 0;
    }

    return Math.round((doneTaskCount.value / props.meeting.action_items.length) * 100);
});
const participants = computed(() =>
    props.meeting.participants.map((participant) => ({
        id: participant.id,
        name: participant.user?.name ?? participant.name ?? participant.email ?? 'Tanpa nama',
        attendance: participant.attendance,
    })),
);

watch(
    () => props.meeting.summaries,
    (summaries) => {
        aiSummaries.value = summaries;
    },
);

async function fetchAiSummaries() {
    try {
        const response = await fetch(route('meetings.ai.status', props.meeting.id), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const payload = (await response.json()) as { summaries: Summary[] };
        aiSummaries.value = payload.summaries;
    } catch {
        return;
    }
}

function stopAiPolling() {
    if (aiPollTimer) {
        clearInterval(aiPollTimer);
        aiPollTimer = null;
    }
}

function startAiPolling() {
    if (aiPollTimer) {
        return;
    }

    aiPollAttempts.value = 0;

    aiPollTimer = setInterval(async () => {
        aiPollAttempts.value += 1;

        await fetchAiSummaries();

        if (!hasPendingAiSummary.value || aiPollAttempts.value >= 120) {
            stopAiPolling();
        }
    }, 2500);
}

watch(
    hasPendingAiSummary,
    (pending) => {
        if (pending) {
            startAiPolling();
        } else {
            stopAiPolling();
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopAiPolling();

    if (copyTimer) {
        clearTimeout(copyTimer);
    }
});

function initials(name?: string | null): string {
    if (!name) {
        return '-';
    }

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

const attachmentUrl = (attachment: Attachment) => route('attachments.download', attachment.id);

function askConfirm(options: { title: string; description: string; label: string; tone?: ConfirmTone }, action: () => void) {
    Object.assign(confirmState, {
        open: true,
        title: options.title,
        description: options.description,
        label: options.label,
        tone: options.tone ?? 'default',
        action,
    });
}

function runConfirm() {
    const action = confirmState.action;
    confirmState.open = false;
    confirmState.action = null;
    action?.();
}

function finalize() {
    const isMeeting = props.meeting.document_type === 'meeting';

    askConfirm(
        {
            title: isMeeting ? 'Finalkan notulen?' : 'Finalkan dokumen?',
            description: 'Setelah difinalkan, isi dokumen dikunci untuk perubahan. Pastikan keputusan dan action item sudah lengkap.',
            label: isMeeting ? 'Finalkan notulen' : 'Finalkan dokumen',
        },
        () => router.post(route('meetings.finalize', props.meeting.id), {}, { preserveScroll: true }),
    );
}

function lockMeeting() {
    router.post(route('meetings.lock', props.meeting.id), {}, { preserveScroll: true });
}

function unlockMeeting() {
    router.post(route('meetings.unlock', props.meeting.id), {}, { preserveScroll: true });
}

function generateAi(tier: AiTier = 'auto') {
    if (props.meeting.confidentiality === 'secret' && !aiConsent.value) {
        if (!window.confirm('Notulen ini rahasia. Setujui pemrosesan AI secara eksplisit?')) {
            return;
        }

        aiConsent.value = true;
    }

    router.post(route('meetings.ai.generate', props.meeting.id), { ai_consent: aiConsent.value, model_tier: tier }, { preserveScroll: true });
}

function summaryToText(summary: Summary): string {
    const lines: string[] = [];

    if (summary.content.ringkasan) {
        lines.push(summary.content.ringkasan);
    }

    if (summary.content.poin?.length) {
        lines.push('', 'Poin utama');

        for (const point of summary.content.poin) {
            lines.push(`- ${point}`);
        }
    }

    if (summary.content.keputusan?.length) {
        lines.push('', 'Keputusan');

        for (const decision of summary.content.keputusan) {
            lines.push(`- ${decision}`);
        }
    }

    if (summary.content.action_items?.length) {
        lines.push('', 'Action item');

        for (const item of summary.content.action_items) {
            const owner = item.pic_name ?? item.division_name;
            const due = item.due_date ? `, tenggat ${formatDate(item.due_date)}` : '';
            lines.push(`- ${item.title}${owner ? ` (${owner})` : ''}${due}`);
        }
    }

    return lines.join('\n');
}

async function copySummary(summary: Summary) {
    try {
        await navigator.clipboard.writeText(summaryToText(summary));
        copiedSummaryId.value = summary.id;

        if (copyTimer) {
            clearTimeout(copyTimer);
        }

        copyTimer = setTimeout(() => {
            copiedSummaryId.value = null;
        }, 2000);
    } catch {
        copiedSummaryId.value = null;
    }
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
    router.post(
        route('action-items.store', props.meeting.id),
        { ...taskForm },
        {
            preserveScroll: true,
            onSuccess: () => {
                Object.assign(taskForm, { title: '', description: '', pic_id: '', division_id: '', due_date: '', status: 'new' });
            },
        },
    );
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
    router.patch(
        route('action-items.update', task.id),
        { ...editTaskForm },
        {
            preserveScroll: true,
            onSuccess: () => {
                editingTaskId.value = null;
            },
        },
    );
}

function deleteTask(task: ActionItem) {
    askConfirm(
        {
            title: 'Hapus action item?',
            description: `"${task.title}" akan dihapus dari dokumen ini. Tindakan ini tidak dapat dibatalkan.`,
            label: 'Hapus action item',
            tone: 'danger',
        },
        () => router.delete(route('action-items.destroy', task.id), { preserveScroll: true }),
    );
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    attachmentForm.file = input.files?.[0] ?? null;
}

function clearAttachmentFile() {
    attachmentForm.file = null;

    if (attachmentInput.value) {
        attachmentInput.value.value = '';
    }
}

function uploadAttachment() {
    router.post(
        route('attachments.store', props.meeting.id),
        {
            file: attachmentForm.file,
            url: attachmentForm.url || null,
            name: attachmentForm.name || null,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                clearAttachmentFile();
                attachmentForm.url = '';
                attachmentForm.name = '';
            },
        },
    );
}

function deleteAttachment(attachment: Attachment) {
    askConfirm(
        {
            title: 'Hapus lampiran?',
            description: `"${attachment.original_name}" akan dihapus dari dokumen ini. Tindakan ini tidak dapat dibatalkan.`,
            label: 'Hapus lampiran',
            tone: 'danger',
        },
        () => router.delete(route('attachments.destroy', attachment.id), { preserveScroll: true }),
    );
}

function restoreVersion(version: Version) {
    askConfirm(
        {
            title: `Pulihkan versi ${version.version}?`,
            description: 'Isi dokumen saat ini akan diganti dengan isi versi tersebut. Buat salinan bila perlu.',
            label: 'Pulihkan versi',
            tone: 'danger',
        },
        () => router.post(route('meetings.versions.restore', { meeting: props.meeting.id, version: version.id }), {}, { preserveScroll: true }),
    );
}
</script>

<template>
    <Head :title="props.meeting.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader :title="props.meeting.title">
                <template #actions>
                    <Button v-if="props.can.share" variant="outline" @click="openShare">
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
                        <Link :href="route('meetings.edit', props.meeting.id)">
                            <Pencil class="size-4" />
                            Sunting
                        </Link>
                    </Button>
                    <Button v-if="props.can.finalize && props.meeting.status === 'draft'" @click="finalize">
                        <CheckCircle2 class="size-4" />
                        {{ props.meeting.document_type === 'meeting' ? 'Finalkan notulen' : 'Finalkan dokumen' }}
                    </Button>
                    <DropdownMenu v-if="user.role === 'admin' && ['final', 'locked'].includes(props.meeting.status)">
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" aria-label="Aksi dokumen lainnya">
                                <MoreHorizontal class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem v-if="props.meeting.status === 'final'" @click="lockMeeting">
                                <Lock class="size-4" />
                                Kunci dokumen
                            </DropdownMenuItem>
                            <DropdownMenuItem v-else @click="unlockMeeting">
                                <Unlock class="size-4" />
                                Buka kunci
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
            </PageHeader>

            <div v-if="errors.meeting" class="rounded-lg border border-destructive/30 bg-destructive/10 p-3.5 text-sm text-destructive">
                {{ errors.meeting }}
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="documentTypeClasses[props.meeting.document_type]">
                    {{ documentTypeLabels[props.meeting.document_type] ?? props.meeting.document_type }}
                </span>
                <StatusBadge kind="meeting" :value="props.meeting.status" />
                <StatusBadge kind="confidentiality" :value="props.meeting.confidentiality" />
                <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold text-muted-foreground">
                    {{ meetingTypeLabels[props.meeting.meeting_type] }}
                </span>
                <span v-if="props.meeting.approved_by" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CheckCircle2 class="size-3.5 text-success" />
                    Difinalkan oleh {{ props.meeting.approved_by.name }} · {{ formatDateTime(props.meeting.finalized_at) }}
                </span>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0 space-y-6">
                    <section class="space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="flex items-center gap-2 text-base font-semibold">
                                <FileText class="size-4 text-muted-foreground" />
                                Isi dokumen
                            </h2>
                            <span class="text-xs text-muted-foreground">Mode baca</span>
                        </div>
                        <MeetingEditor :model-value="props.meeting.content" :editable="false" />
                    </section>

                    <section class="overflow-hidden rounded-lg border border-ai/25 bg-ai-surface/60 shadow-card" aria-labelledby="ai-summary-heading">
                        <div class="flex flex-col gap-4 border-b border-ai/15 p-5 md:flex-row md:items-start md:justify-between md:p-6">
                            <div class="flex gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-md bg-ai/10 text-ai">
                                    <Sparkles class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 id="ai-summary-heading" class="text-base font-semibold">Resume AI</h2>
                                        <span class="rounded-full bg-card/80 px-2.5 py-0.5 text-[11px] font-medium text-ai ring-1 ring-ai/20"
                                            >Dibuat oleh AI</span
                                        >
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                        Ringkasan otomatis dari isi notulen. Periksa kembali sebelum dipakai.
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <Button v-if="hasPendingAiSummary" variant="outline" size="sm" disabled>
                                    <Loader2 class="size-4 animate-spin motion-reduce:animate-none" />
                                    Memproses
                                </Button>
                                <DropdownMenu v-else-if="canGenerate">
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm">
                                            <Sparkles class="size-4" />
                                            {{ aiSummaries.length ? 'Buat ulang' : 'Buat resume' }}
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-64">
                                        <DropdownMenuItem class="flex-col items-start gap-0.5" @click="generateAi('auto')">
                                            <span class="font-medium">Auto · DeepSeek 4.1 Flash</span>
                                            <span class="text-xs text-muted-foreground">Cepat dan hemat untuk resume harian</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem class="flex-col items-start gap-0.5" @click="generateAi('expert')">
                                            <span class="font-medium">Expert · GPT-6.1 Sol</span>
                                            <span class="text-xs text-muted-foreground">Analisis lebih mendalam untuk rapat penting</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        <div class="space-y-4 p-5 md:p-6">
                            <div v-if="aiSummaries.length === 0" class="rounded-lg border border-dashed border-ai/25 bg-card/60 p-5">
                                <p class="text-sm font-medium">Belum ada resume</p>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                    {{
                                        canGenerate
                                            ? 'Buat resume untuk merangkum poin penting, keputusan, dan action item.'
                                            : 'Resume belum tersedia untuk dokumen ini.'
                                    }}
                                </p>
                            </div>

                            <article v-for="summary in aiSummaries" :key="summary.id" class="rounded-lg border border-ai/15 bg-card/85 p-4 shadow-sm">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                    <span class="font-medium text-ai">Versi {{ summary.version }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ summaryStatusLabels[summary.status] ?? summary.status }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ summaryGenerationLabels[summary.generation_status] ?? summary.generation_status }}</span>
                                    <span v-if="summary.model" aria-hidden="true">·</span>
                                    <span v-if="summary.model">{{ summaryModelLabels[summary.model] ?? summary.model }}</span>
                                </div>

                                <div v-if="['queued', 'processing'].includes(summary.generation_status)" class="mt-3 space-y-2" aria-live="polite">
                                    <p class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Loader2 class="size-3.5 animate-spin motion-reduce:animate-none" />
                                        Sedang membuat resume
                                    </p>
                                    <Skeleton class="h-3 w-full" />
                                    <Skeleton class="h-3 w-5/6" />
                                    <Skeleton class="h-3 w-2/3" />
                                </div>

                                <p
                                    v-if="summary.generation_status === 'failed'"
                                    class="mt-3 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
                                >
                                    Gagal membuat resume: {{ summary.error_message ?? 'Layanan AI tidak tersedia.' }}
                                </p>

                                <p v-if="summary.content.ringkasan" class="mt-3 text-sm leading-7 text-foreground">{{ summary.content.ringkasan }}</p>

                                <div v-if="summary.content.poin?.length" class="mt-4">
                                    <p class="text-xs font-semibold text-foreground">Poin utama</p>
                                    <ul class="mt-2 space-y-1.5">
                                        <li v-for="(point, index) in summary.content.poin" :key="index" class="flex gap-2 text-sm leading-6">
                                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true"></span>
                                            <span>{{ point }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="summary.content.keputusan?.length" class="mt-4">
                                    <p class="text-xs font-semibold text-foreground">Keputusan</p>
                                    <ul class="mt-2 space-y-1.5">
                                        <li v-for="(decision, index) in summary.content.keputusan" :key="index" class="flex gap-2 text-sm leading-6">
                                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true"></span>
                                            <span>{{ decision }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="summary.content.action_items?.length" class="mt-4">
                                    <p class="text-xs font-semibold text-foreground">Usulan action item</p>
                                    <ul class="mt-2 space-y-1.5">
                                        <li v-for="(item, index) in summary.content.action_items" :key="index" class="flex gap-2 text-sm leading-6">
                                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true"></span>
                                            <span>{{ item.title }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="summary.status === 'draft' && summary.generation_status === 'completed' && canManage"
                                    class="mt-4 flex flex-wrap gap-2"
                                >
                                    <Button size="sm" @click="approveSummary(summary)">Setujui</Button>
                                    <Button size="sm" variant="outline" @click="rejectSummary(summary)">Tolak</Button>
                                    <Button
                                        v-if="summary.content.action_items?.length"
                                        size="sm"
                                        variant="outline"
                                        @click="applySummaryActions(summary)"
                                    >
                                        Terapkan action item
                                    </Button>
                                </div>

                                <div v-if="summary.generation_status === 'completed' && summary.content.ringkasan" class="mt-4 flex justify-end">
                                    <Button size="sm" variant="ghost" @click="copySummary(summary)">
                                        <Check v-if="copiedSummaryId === summary.id" class="size-4" />
                                        <Copy v-else class="size-4" />
                                        {{ copiedSummaryId === summary.id ? 'Tersalin' : 'Salin resume' }}
                                    </Button>
                                </div>
                            </article>
                        </div>
                    </section>

                    <Card>
                        <CardHeader class="flex-row items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-base">Keputusan</CardTitle>
                                <p class="text-sm text-muted-foreground">Hasil yang disepakati dalam dokumen ini.</p>
                            </div>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground">{{
                                props.meeting.decisions.length
                            }}</span>
                        </CardHeader>
                        <CardContent>
                            <p v-if="props.meeting.decisions.length === 0" class="text-sm text-muted-foreground">Belum ada keputusan tercatat.</p>
                            <ol v-else class="relative space-y-4 border-l border-border pl-5">
                                <li v-for="decision in props.meeting.decisions" :key="decision.id" class="relative">
                                    <span
                                        class="absolute -left-[27px] top-1.5 size-3 rounded-full border-2 border-card bg-primary"
                                        aria-hidden="true"
                                    ></span>
                                    <p class="text-sm leading-6">{{ decision.content }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                        <span v-if="decision.topic" class="text-topic">#{{ decision.topic.name }}</span>
                                        <span v-if="decision.decided_at">{{ formatDate(decision.decided_at) }}</span>
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="gap-3">
                            <div class="flex flex-row items-start justify-between gap-3">
                                <div>
                                    <CardTitle class="text-base">Action item</CardTitle>
                                    <p class="text-sm text-muted-foreground">PIC dan tenggat wajib sebelum finalisasi.</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground">
                                    {{ doneTaskCount }}/{{ props.meeting.action_items.length }}
                                </span>
                            </div>
                            <div v-if="props.meeting.action_items.length" class="flex items-center gap-3">
                                <div
                                    class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                    role="progressbar"
                                    :aria-valuenow="taskProgress"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-label="Progres action item"
                                >
                                    <div
                                        class="h-full rounded-full bg-success transition-[width] duration-popover ease-ui motion-reduce:transition-none"
                                        :style="{ width: `${taskProgress}%` }"
                                    ></div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ taskProgress }}%</span>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <form
                                v-if="canManage && props.meeting.status === 'draft'"
                                class="grid gap-3 rounded-lg border border-border bg-muted/20 p-4 sm:grid-cols-2 xl:grid-cols-4"
                                @submit.prevent="addTask"
                            >
                                <div class="xl:col-span-2">
                                    <label for="task-title" class="sr-only">Judul tugas</label>
                                    <Input id="task-title" v-model="taskForm.title" required placeholder="Tugas baru" />
                                </div>
                                <div class="xl:col-span-2">
                                    <label for="task-description" class="sr-only">Deskripsi tugas</label>
                                    <Input id="task-description" v-model="taskForm.description" placeholder="Deskripsi singkat (opsional)" />
                                </div>
                                <div>
                                    <label for="task-pic" class="sr-only">PIC</label>
                                    <Select id="task-pic" v-model="taskForm.pic_id">
                                        <option value="">PIC</option>
                                        <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">{{ person.name }}</option>
                                    </Select>
                                </div>
                                <div>
                                    <label for="task-division" class="sr-only">Divisi</label>
                                    <Select id="task-division" v-model="taskForm.division_id">
                                        <option value="">Divisi</option>
                                        <option v-for="division in taskDivisions" :key="division.id" :value="String(division.id)">
                                            {{ division.name }}
                                        </option>
                                    </Select>
                                </div>
                                <div>
                                    <label for="task-due-date" class="sr-only">Tenggat</label>
                                    <Input id="task-due-date" v-model="taskForm.due_date" type="date" aria-label="Tenggat" />
                                </div>
                                <Button type="submit">
                                    <Plus class="size-4" />
                                    Tambah
                                </Button>
                            </form>

                            <div
                                v-if="props.meeting.action_items.length === 0"
                                class="rounded-lg border border-dashed border-border px-4 py-8 text-center"
                            >
                                <ListChecks class="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
                                <p class="mt-2 text-sm font-medium">Belum ada action item</p>
                                <p class="mt-1 text-xs text-muted-foreground">Tambahkan tugas, PIC, dan tenggat agar tindak lanjut tidak terlewat.</p>
                            </div>

                            <div v-else class="space-y-2">
                                <div v-for="task in props.meeting.action_items" :key="task.id" class="rounded-lg border border-border bg-card p-3.5">
                                    <div v-if="editingTaskId === task.id" class="space-y-3">
                                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                            <div class="xl:col-span-2">
                                                <label for="edit-task-title" class="sr-only">Judul tugas</label>
                                                <Input id="edit-task-title" v-model="editTaskForm.title" />
                                            </div>
                                            <div>
                                                <label for="edit-task-pic" class="sr-only">PIC</label>
                                                <Select id="edit-task-pic" v-model="editTaskForm.pic_id">
                                                    <option value="">PIC</option>
                                                    <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">
                                                        {{ person.name }}
                                                    </option>
                                                </Select>
                                            </div>
                                            <div>
                                                <label for="edit-task-division" class="sr-only">Divisi</label>
                                                <Select id="edit-task-division" v-model="editTaskForm.division_id">
                                                    <option value="">Divisi</option>
                                                    <option v-for="division in taskDivisions" :key="division.id" :value="String(division.id)">
                                                        {{ division.name }}
                                                    </option>
                                                </Select>
                                            </div>
                                            <div>
                                                <label for="edit-task-due-date" class="sr-only">Tenggat</label>
                                                <Input id="edit-task-due-date" v-model="editTaskForm.due_date" type="date" />
                                            </div>
                                            <div>
                                                <label for="edit-task-status" class="sr-only">Status</label>
                                                <Select id="edit-task-status" v-model="editTaskForm.status">
                                                    <option value="new">Baru</option>
                                                    <option value="in_progress">Berjalan</option>
                                                    <option value="done">Selesai</option>
                                                    <option value="cancelled">Dibatalkan</option>
                                                </Select>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <Button size="sm" @click="saveEditTask(task)">Simpan</Button>
                                            <Button size="sm" variant="ghost" @click="editingTaskId = null">
                                                <X class="size-4" />
                                                Batal
                                            </Button>
                                        </div>
                                    </div>

                                    <div v-else class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 gap-3">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                                <ListChecks class="size-4" aria-hidden="true" />
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold leading-6">{{ task.title }}</p>
                                                <p v-if="task.description" class="mt-0.5 text-xs leading-5 text-muted-foreground">
                                                    {{ task.description }}
                                                </p>
                                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <span
                                                            class="flex size-5 shrink-0 items-center justify-center rounded-full bg-secondary text-[9px] font-semibold text-secondary-foreground"
                                                        >
                                                            {{ initials(task.pic?.name ?? task.pic_name ?? task.division?.name) }}
                                                        </span>
                                                        {{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada PIC' }}
                                                    </span>
                                                    <span v-if="task.division?.name && (task.pic?.name || task.pic_name)">{{
                                                        task.division.name
                                                    }}</span>
                                                    <span
                                                        class="inline-flex items-center gap-1"
                                                        :class="task.is_overdue ? 'font-medium text-destructive' : ''"
                                                    >
                                                        <AlertCircle v-if="task.is_overdue" class="size-3.5" aria-hidden="true" />
                                                        {{ task.is_overdue ? 'Lewat tenggat' : 'Tenggat' }} {{ formatDate(task.due_date) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <template v-if="canEditTask(task)">
                                                <label :for="`task-status-${task.id}`" class="sr-only">Status {{ task.title }}</label>
                                                <Select
                                                    :id="`task-status-${task.id}`"
                                                    :model-value="task.status"
                                                    class="h-8 w-32 text-xs"
                                                    @update:model-value="updateTaskStatus(task, String($event))"
                                                >
                                                    <option value="new">Baru</option>
                                                    <option value="in_progress">Berjalan</option>
                                                    <option value="done">Selesai</option>
                                                    <option value="cancelled">Dibatalkan</option>
                                                </Select>
                                            </template>
                                            <StatusBadge v-else kind="action" :value="task.status" />

                                            <div v-if="canManage" class="flex items-center">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    class="size-8"
                                                    :aria-label="`Sunting ${task.title}`"
                                                    @click="startEditTask(task)"
                                                >
                                                    <Pencil class="size-4" />
                                                </Button>
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    class="size-8"
                                                    :aria-label="`Hapus ${task.title}`"
                                                    @click="deleteTask(task)"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Lampiran</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <form
                                v-if="canManage"
                                class="space-y-3 rounded-lg border border-dashed border-border bg-muted/20 p-4"
                                @submit.prevent="uploadAttachment"
                            >
                                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                    <div class="space-y-1.5">
                                        <span class="text-sm font-medium">Berkas</span>
                                        <div
                                            class="flex min-h-10 flex-wrap items-center gap-2 rounded-md border border-dashed border-border bg-card px-3 py-1.5"
                                        >
                                            <input
                                                ref="attachmentInput"
                                                id="attachment-file"
                                                type="file"
                                                class="peer sr-only"
                                                @change="onFileChange"
                                            />
                                            <label
                                                for="attachment-file"
                                                class="inline-flex cursor-pointer items-center gap-2 rounded-sm text-sm font-medium text-primary outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2"
                                            >
                                                <Paperclip class="size-4" aria-hidden="true" />
                                                Pilih berkas
                                            </label>
                                            <span class="min-w-0 flex-1 truncate text-xs text-muted-foreground">
                                                {{ attachmentForm.file?.name ?? 'Belum ada berkas dipilih' }}
                                            </span>
                                            <button
                                                v-if="attachmentForm.file"
                                                type="button"
                                                class="rounded-sm p-1 text-muted-foreground transition-colors duration-fast hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                aria-label="Batalkan berkas"
                                                @click="clearAttachmentFile"
                                            >
                                                <X class="size-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <Button type="submit">
                                        <FileUp class="size-4" />
                                        Unggah
                                    </Button>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <label for="attachment-url" class="sr-only">Tautan lampiran</label>
                                        <Input id="attachment-url" v-model="attachmentForm.url" type="url" placeholder="https://" />
                                    </div>
                                    <div>
                                        <label for="attachment-name" class="sr-only">Nama lampiran</label>
                                        <Input id="attachment-name" v-model="attachmentForm.name" placeholder="Nama lampiran (opsional)" />
                                    </div>
                                </div>
                            </form>

                            <p v-if="props.meeting.attachments.length === 0" class="text-sm text-muted-foreground">Belum ada lampiran.</p>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="attachment in props.meeting.attachments"
                                    :key="attachment.id"
                                    class="flex items-center justify-between gap-3 rounded-md border border-border p-2.5"
                                >
                                    <a
                                        :href="attachmentUrl(attachment)"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex min-w-0 items-center gap-2.5 text-sm hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                            <Paperclip class="size-4" aria-hidden="true" />
                                        </span>
                                        <span class="truncate">{{ attachment.original_name }}</span>
                                    </a>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <span class="rounded-full bg-muted px-2 py-1 text-[10px] font-medium text-muted-foreground">{{
                                            attachment.kind
                                        }}</span>
                                        <Button
                                            v-if="canManage"
                                            size="icon"
                                            variant="ghost"
                                            class="size-8"
                                            :aria-label="`Hapus ${attachment.original_name}`"
                                            @click="deleteAttachment(attachment)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <aside class="min-w-0 space-y-4">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <Info class="size-4 text-muted-foreground" />
                                Informasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-3 text-sm">
                                <div class="flex gap-3">
                                    <CalendarClock class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <dt class="text-xs text-muted-foreground">
                                            {{ props.meeting.document_type === 'meeting' ? 'Jadwal rapat' : 'Tanggal dokumen' }}
                                        </dt>
                                        <dd class="mt-0.5">{{ formatDateTime(props.meeting.scheduled_at) }}</dd>
                                    </div>
                                </div>
                                <div v-if="props.meeting.location" class="flex gap-3">
                                    <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <dt class="text-xs text-muted-foreground">Lokasi</dt>
                                        <dd class="mt-0.5">{{ props.meeting.location }}</dd>
                                    </div>
                                </div>
                                <div v-if="props.meeting.meeting_link" class="flex gap-3">
                                    <ExternalLink class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <dt class="text-xs text-muted-foreground">Tautan rapat</dt>
                                        <dd class="mt-0.5">
                                            <a
                                                :href="props.meeting.meeting_link"
                                                target="_blank"
                                                rel="noopener"
                                                class="break-all text-primary underline underline-offset-2"
                                            >
                                                Buka tautan
                                            </a>
                                        </dd>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <UserRound class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <dt class="text-xs text-muted-foreground">
                                            {{ props.meeting.document_type === 'meeting' ? 'Notulis' : 'Pembuat' }}
                                        </dt>
                                        <dd class="mt-0.5">{{ props.meeting.creator?.name ?? '-' }}</dd>
                                    </div>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">Divisi</dt>
                                    <dd class="mt-0.5">{{ props.meeting.division?.name ?? 'Tanpa divisi' }}</dd>
                                </div>
                                <div v-if="props.meeting.template">
                                    <dt class="text-xs text-muted-foreground">Template</dt>
                                    <dd class="mt-0.5">{{ props.meeting.template.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">Kerahasiaan</dt>
                                    <dd class="mt-0.5">{{ confidentialityLabels[props.meeting.confidentiality] }}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <Users class="size-4 text-muted-foreground" />
                                Peserta
                            </CardTitle>
                            <span class="text-xs text-muted-foreground">{{ participants.length }}</span>
                        </CardHeader>
                        <CardContent>
                            <div v-if="participants.length" class="space-y-3">
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
                            <p v-else class="text-sm text-muted-foreground">Belum ada peserta.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <Tags class="size-4 text-muted-foreground" />
                                Tag & topik
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.meeting.tags.length" class="flex flex-wrap gap-1.5">
                                <TagChip
                                    v-for="tag in props.meeting.tags"
                                    :key="tag.id"
                                    :kind="tag.type"
                                    :label="tag.target_name ?? 'Tanpa nama'"
                                    :href="tagHref(tag)"
                                />
                            </div>
                            <p v-else class="text-sm text-muted-foreground">Belum ada tag.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <History class="size-4 text-muted-foreground" />
                                Riwayat versi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p v-if="props.meeting.versions.length === 0" class="text-sm text-muted-foreground">Belum ada snapshot versi.</p>
                            <div v-else class="space-y-3">
                                <div v-for="version in props.meeting.versions" :key="version.id" class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium">Versi {{ version.version }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ version.reason }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDateTime(version.created_at) }}</p>
                                    </div>
                                    <Button v-if="canManage" size="sm" variant="outline" @click="restoreVersion(version)">Pulihkan</Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>

        <Dialog v-model:open="shareOpen">
            <DialogContent class="sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submitShare">
                    <DialogHeader>
                        <DialogTitle>Bagikan dokumen</DialogTitle>
                        <DialogDescription>Kirim tautan dan, jika dipilih, salinan PDF ke alamat email penerima.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-1.5">
                        <label for="share-recipients" class="text-sm font-medium">Email penerima</label>
                        <textarea
                            id="share-recipients"
                            v-model="shareForm.recipients"
                            rows="2"
                            class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm leading-6 shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui placeholder:text-muted-foreground hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            placeholder="nama@perusahaan.com, rekan@perusahaan.com"
                            aria-describedby="share-recipients-hint share-recipients-error"
                        />
                        <p id="share-recipients-hint" class="text-xs text-muted-foreground">Pisahkan dengan koma. Maksimal 10 penerima.</p>
                        <p v-if="shareForm.errors.recipients" id="share-recipients-error" class="text-xs font-medium text-destructive">
                            {{ shareForm.errors.recipients }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="share-message" class="text-sm font-medium">Pesan (opsional)</label>
                        <textarea
                            id="share-message"
                            v-model="shareForm.message"
                            rows="3"
                            class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm leading-6 shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui placeholder:text-muted-foreground hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            placeholder="Tambahkan catatan untuk penerima"
                        />
                        <p v-if="shareForm.errors.message" class="text-xs font-medium text-destructive">{{ shareForm.errors.message }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="shareForm.include_pdf" type="checkbox" class="size-4 rounded border-input accent-primary" />
                        Lampirkan salinan PDF
                    </label>

                    <div
                        v-if="requiresShareConfirmation"
                        class="space-y-2 rounded-md border border-warning/30 bg-warning/10 p-3 text-xs text-warning"
                    >
                        <p>
                            Dokumen ini berlabel <strong>{{ confidentialityLabels[props.meeting.confidentiality] }}</strong
                            >. Pastikan seluruh penerima berhak menerima dokumen.
                        </p>
                        <label class="flex items-center gap-2">
                            <input v-model="shareForm.confirm_share" type="checkbox" class="size-4 rounded border-input accent-primary" />
                            Saya mengonfirmasi penerima berhak.
                        </label>
                        <p v-if="shareForm.errors.confirm_share" class="font-medium">{{ shareForm.errors.confirm_share }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="shareOpen = false">Batal</Button>
                        <Button type="submit" :loading="shareForm.processing">
                            <Share2 class="size-4" />
                            Kirim email
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="confirmState.open">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ confirmState.title }}</DialogTitle>
                    <DialogDescription>{{ confirmState.description }}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="confirmState.open = false">Batal</Button>
                    <Button :variant="confirmState.tone === 'danger' ? 'destructive' : 'default'" @click="runConfirm">{{
                        confirmState.label
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
