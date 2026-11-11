<script setup lang="ts">
import ActionItemFeedbackDialog from '@/components/ActionItemFeedbackDialog.vue';
import AttendanceBadge from '@/components/AttendanceBadge.vue';
import AvatarGroup from '@/components/AvatarGroup.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import EditorSkeleton from '@/components/editor/EditorSkeleton.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TagChip from '@/components/TagChip.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { confidentialityLabels, documentTypeLabels, formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
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
    AlertTriangle,
    Building2,
    CalendarClock,
    CheckCircle2,
    Copy,
    Download,
    ExternalLink,
    FileText,
    FileUp,
    History,
    ListChecks,
    Loader2,
    Lock,
    MapPin,
    MessageSquare,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Plus,
    RefreshCw,
    Share2,
    Sparkles,
    Trash2,
    Unlock,
    UserRound,
    X,
} from 'lucide-vue-next';
import { computed, defineAsyncComponent, onBeforeUnmount, reactive, ref, watch } from 'vue';

const MeetingEditor = defineAsyncComponent({
    loader: () => import('@/components/MeetingEditor.vue'),
    loadingComponent: EditorSkeleton,
    delay: 120,
});

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
const toast = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dokumen', href: '/notulen' },
    { title: props.meeting.title, href: `/notulen/${props.meeting.id}` },
];

const aiConsent = ref(false);
const editingTaskId = ref<number | null>(null);
const feedbackTaskId = ref<number | null>(null);
const shareOpen = ref(false);

const confirmState = reactive<{
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    variant: 'danger' | 'default';
    action: (() => void) | null;
}>({
    open: false,
    title: '',
    description: '',
    confirmLabel: 'Lanjutkan',
    variant: 'danger',
    action: null,
});

function askConfirm(options: { title: string; description?: string; confirmLabel?: string; variant?: 'danger' | 'default'; action: () => void }) {
    Object.assign(confirmState, {
        title: options.title,
        description: options.description ?? '',
        confirmLabel: options.confirmLabel ?? 'Lanjutkan',
        variant: options.variant ?? 'danger',
        action: options.action,
        open: true,
    });
}

function runConfirmedAction() {
    const action = confirmState.action;
    confirmState.open = false;
    confirmState.action = null;
    action?.();
}

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
const selectedFeedbackTask = computed(() => props.meeting.action_items.find((task) => task.id === feedbackTaskId.value) ?? null);
const requiresShareConfirmation = computed(() => ['restricted', 'secret'].includes(props.meeting.confidentiality));
const taskUsers = computed(() => props.users ?? []);
const taskDivisions = computed(() => props.divisions ?? []);

const participantUsers = computed(() =>
    props.meeting.participants.map((participant) => ({
        id: participant.user?.id ?? participant.id,
        name: participant.user?.name ?? participant.name ?? participant.email ?? 'Peserta',
    })),
);

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

const summaryStatusVariants: Record<string, BadgeVariants['variant']> = {
    draft: 'warning',
    approved: 'success',
    rejected: 'danger',
};

const summaryGenerationVariants: Record<string, BadgeVariants['variant']> = {
    queued: 'neutral',
    processing: 'info',
    completed: 'success',
    failed: 'danger',
};

const aiSummaries = ref<Summary[]>([...props.meeting.summaries]);
const aiPollAttempts = ref(0);
let aiPollTimer: ReturnType<typeof setInterval> | null = null;

const hasPendingAiSummary = computed(() => aiSummaries.value.some((summary) => ['queued', 'processing'].includes(summary.generation_status)));

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

onBeforeUnmount(stopAiPolling);

const attachmentUrl = (attachment: Attachment) => route('attachments.download', attachment.id);

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

function finalize() {
    askConfirm({
        title: props.meeting.document_type === 'meeting' ? 'Finalkan notulen ini?' : 'Finalkan dokumen ini?',
        description: 'Isi akan dikunci untuk perubahan. Pastikan keputusan dan action item sudah lengkap.',
        confirmLabel: 'Finalkan',
        variant: 'default',
        action: () => router.post(route('meetings.finalize', props.meeting.id), {}, { preserveScroll: true }),
    });
}

function lockMeeting() {
    askConfirm({
        title: 'Kunci dokumen ini?',
        description: 'Dokumen terkunci tidak dapat diubah sampai kunci dibuka oleh admin.',
        confirmLabel: 'Kunci',
        variant: 'default',
        action: () => router.post(route('meetings.lock', props.meeting.id), {}, { preserveScroll: true }),
    });
}

function unlockMeeting() {
    askConfirm({
        title: 'Buka kunci dokumen?',
        description: 'Dokumen dapat diubah kembali setelah kunci dibuka.',
        confirmLabel: 'Buka kunci',
        variant: 'default',
        action: () => router.post(route('meetings.unlock', props.meeting.id), {}, { preserveScroll: true }),
    });
}

function generateAi(tier: AiTier = 'auto') {
    if (props.meeting.confidentiality === 'secret' && !aiConsent.value) {
        askConfirm({
            title: 'Setujui pemrosesan AI?',
            description: 'Notulen ini berlabel Rahasia. Isi akan dikirim ke penyedia AI untuk diringkas.',
            confirmLabel: 'Setujui dan proses',
            variant: 'default',
            action: () => {
                aiConsent.value = true;
                router.post(route('meetings.ai.generate', props.meeting.id), { ai_consent: true, model_tier: tier }, { preserveScroll: true });
            },
        });

        return;
    }

    router.post(route('meetings.ai.generate', props.meeting.id), { ai_consent: aiConsent.value, model_tier: tier }, { preserveScroll: true });
}

function summaryText(summary: Summary): string {
    const lines: string[] = [];

    if (summary.content.ringkasan) {
        lines.push(summary.content.ringkasan);
    }

    if (summary.content.poin?.length) {
        lines.push(summary.content.poin.map((point) => `- ${point}`).join('\n'));
    }

    if (summary.content.keputusan?.length) {
        lines.push(`Keputusan\n${summary.content.keputusan.map((decision) => `- ${decision}`).join('\n')}`);
    }

    if (summary.content.action_items?.length) {
        lines.push(`Action item\n${summary.content.action_items.map((item) => `- ${item.title}`).join('\n')}`);
    }

    return lines.join('\n\n');
}

async function copySummary(summary: Summary) {
    try {
        await navigator.clipboard.writeText(summaryText(summary));
        toast.success('Resume disalin', 'Ringkasan AI disalin ke papan klip.');
    } catch {
        toast.error('Gagal menyalin', 'Browser menolak akses papan klip.');
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

function updateTaskStatus(task: ActionItem, status: string | number) {
    router.patch(route('action-items.status', task.id), { status: String(status) }, { preserveScroll: true });
}

function canEditTask(task: ActionItem) {
    return canManage.value || task.pic?.id === user.value.id;
}

function openFeedback(task: ActionItem) {
    feedbackTaskId.value = task.id;
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
    askConfirm({
        title: 'Hapus action item?',
        description: `"${task.title}" akan dihapus dari dokumen ini.`,
        confirmLabel: 'Hapus',
        variant: 'danger',
        action: () => router.delete(route('action-items.destroy', task.id), { preserveScroll: true }),
    });
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    attachmentForm.file = input.files?.[0] ?? null;
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
                attachmentForm.file = null;
                attachmentForm.url = '';
                attachmentForm.name = '';
            },
        },
    );
}

function deleteAttachment(attachment: Attachment) {
    askConfirm({
        title: 'Hapus lampiran?',
        description: `"${attachment.original_name}" akan dihapus dari dokumen ini.`,
        confirmLabel: 'Hapus',
        variant: 'danger',
        action: () => router.delete(route('attachments.destroy', attachment.id), { preserveScroll: true }),
    });
}

function restoreVersion(version: Version) {
    askConfirm({
        title: `Pulihkan versi ${version.version}?`,
        description: 'Isi dokumen saat ini akan diganti dengan isi versi tersebut.',
        confirmLabel: 'Pulihkan',
        variant: 'default',
        action: () =>
            router.post(route('meetings.versions.restore', { meeting: props.meeting.id, version: version.id }), {}, { preserveScroll: true }),
    });
}
</script>

<template>
    <Head :title="props.meeting.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader :title="props.meeting.title">
                <template #actions>
                    <Button v-if="props.can.update" variant="outline" as-child>
                        <Link :href="route('meetings.edit', props.meeting.id)">
                            <Pencil class="size-4" aria-hidden="true" />
                            Sunting
                        </Link>
                    </Button>
                    <Button v-if="props.can.share" variant="outline" @click="openShare">
                        <Share2 class="size-4" aria-hidden="true" />
                        Bagikan
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                <Download class="size-4" aria-hidden="true" />
                                Ekspor
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52">
                            <DropdownMenuItem as-child>
                                <a :href="route('meetings.export.pdf', props.meeting.id)" class="flex w-full items-center gap-2">
                                    <Download class="size-4" aria-hidden="true" />
                                    PDF
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <a :href="route('meetings.export.word', props.meeting.id)" class="flex w-full items-center gap-2">
                                    <Download class="size-4" aria-hidden="true" />
                                    Word (.docx)
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button v-if="hasPendingAiSummary" variant="outline" disabled>
                        <Loader2 class="size-4 animate-spin" aria-hidden="true" />
                        Memproses…
                    </Button>
                    <DropdownMenu v-else-if="props.can.generateAi && props.meeting.status !== 'locked'">
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                <Sparkles class="size-4" aria-hidden="true" />
                                Resume AI
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
                    <Button v-if="props.can.finalize && props.meeting.status === 'draft'" @click="finalize">
                        {{ props.meeting.document_type === 'meeting' ? 'Finalkan notulen' : 'Finalkan dokumen' }}
                    </Button>
                    <DropdownMenu v-if="user.role === 'admin' && ['final', 'locked'].includes(props.meeting.status)">
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" aria-label="Aksi lainnya">
                                <MoreHorizontal class="size-4" aria-hidden="true" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem v-if="props.meeting.status === 'final'" @click="lockMeeting">
                                <Lock class="size-4" aria-hidden="true" />
                                Kunci dokumen
                            </DropdownMenuItem>
                            <DropdownMenuItem v-else @click="unlockMeeting">
                                <Unlock class="size-4" aria-hidden="true" />
                                Buka kunci
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
            </PageHeader>

            <div
                v-if="errors.meeting"
                class="flex items-start gap-2 rounded-md border border-destructive/20 bg-destructive-soft p-3 text-sm text-destructive-strong"
                role="alert"
            >
                <AlertTriangle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                {{ errors.meeting }}
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <DocumentTypeBadge :type="props.meeting.document_type" />
                <StatusBadge kind="meeting" :value="props.meeting.status" />
                <StatusBadge kind="confidentiality" :value="props.meeting.confidentiality" />
                <Badge variant="outline">{{ meetingTypeLabels[props.meeting.meeting_type] }}</Badge>
                <span v-if="props.meeting.approved_by" class="flex items-center gap-1 text-xs text-muted-foreground">
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    Difinalkan oleh {{ props.meeting.approved_by.name }} · {{ formatDateTime(props.meeting.finalized_at) }}
                </span>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
                <div class="min-w-0 space-y-6">
                    <section class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-heading font-semibold leading-tight tracking-tight">Isi dokumen</h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ documentTypeLabels[props.meeting.document_type] ?? 'Dokumen' }} · dibaca
                                    {{ formatDate(props.meeting.scheduled_at) }}
                                </p>
                            </div>
                            <Badge variant="neutral">{{ props.meeting.status === 'draft' ? 'Draf' : 'Final' }}</Badge>
                        </div>
                        <MeetingEditor :model-value="props.meeting.content" :editable="false" />
                    </section>

                    <Card>
                        <CardHeader>
                            <CardTitle>Keputusan</CardTitle>
                            <CardDescription>Keputusan resmi yang tercatat pada dokumen ini.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <EmptyState v-if="props.meeting.decisions.length === 0" compact :icon="CheckCircle2" title="Belum ada keputusan" />
                            <ol v-else class="space-y-3">
                                <li v-for="(decision, index) in props.meeting.decisions" :key="decision.id" class="flex gap-3">
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-semibold tabular-nums text-accent-foreground"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <div class="min-w-0 space-y-1">
                                        <p class="text-sm leading-relaxed text-foreground">{{ decision.content }}</p>
                                        <TagChip
                                            v-if="decision.topic"
                                            type="topic"
                                            :label="decision.topic.name"
                                            :href="route('topics.show', decision.topic.slug)"
                                        />
                                    </div>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <Card class="border-ai-border bg-ai-soft/40 shadow-soft-md">
                        <CardHeader class="flex-col gap-3 space-y-0 sm:flex-row sm:items-start">
                            <div class="flex min-w-0 items-start gap-3">
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-surface text-ai-foreground shadow-soft-xs ring-1 ring-ai-border"
                                >
                                    <Sparkles class="size-5" aria-hidden="true" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <CardTitle>Resume AI</CardTitle>
                                        <Badge variant="ai">Dibuat oleh AI</Badge>
                                    </div>
                                    <CardDescription class="mt-1">Ringkasan otomatis dari isi notulen. Verifikasi sebelum dipakai.</CardDescription>
                                </div>
                            </div>
                            <Button
                                v-if="canManage && props.meeting.status !== 'locked'"
                                variant="outline"
                                size="sm"
                                class="w-full sm:w-auto sm:shrink-0"
                                :loading="hasPendingAiSummary"
                                :disabled="hasPendingAiSummary"
                                @click="generateAi('auto')"
                            >
                                <RefreshCw v-if="!hasPendingAiSummary" class="size-3.5" aria-hidden="true" />
                                {{ aiSummaries.length ? 'Buat ulang' : 'Buat resume' }}
                            </Button>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <EmptyState
                                v-if="aiSummaries.length === 0"
                                compact
                                :icon="Sparkles"
                                title="Belum ada resume"
                                description="Buat ringkasan otomatis dari isi notulen, lalu verifikasi hasilnya."
                                class="border-ai-border/70 bg-surface/60"
                            />

                            <div
                                v-for="summary in aiSummaries"
                                :key="summary.id"
                                class="space-y-4 rounded-md border border-ai-border/70 bg-surface/80 p-4"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge variant="ai">v{{ summary.version }}</Badge>
                                    <Badge :variant="summaryStatusVariants[summary.status] ?? 'neutral'">
                                        {{ summaryStatusLabels[summary.status] ?? summary.status }}
                                    </Badge>
                                    <Badge :variant="summaryGenerationVariants[summary.generation_status] ?? 'neutral'">
                                        {{ summaryGenerationLabels[summary.generation_status] ?? summary.generation_status }}
                                    </Badge>
                                    <span v-if="summary.model" class="text-xs text-muted-foreground">
                                        {{ summaryModelLabels[summary.model] ?? summary.model }}
                                    </span>
                                </div>

                                <div
                                    v-if="['queued', 'processing'].includes(summary.generation_status)"
                                    class="space-y-2"
                                    aria-busy="true"
                                    aria-live="polite"
                                >
                                    <p class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Loader2 class="size-4 animate-spin" aria-hidden="true" />
                                        Resume sedang diproses…
                                    </p>
                                    <Skeleton class="h-4 w-4/5" />
                                    <Skeleton class="h-4 w-full" />
                                    <Skeleton class="h-4 w-3/5" />
                                </div>

                                <p
                                    v-if="summary.generation_status === 'failed'"
                                    class="flex items-start gap-2 rounded-md bg-destructive-soft p-3 text-sm text-destructive-strong"
                                    role="alert"
                                >
                                    <AlertTriangle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    Gagal membuat resume: {{ summary.error_message ?? 'Layanan AI tidak tersedia.' }}
                                </p>

                                <template v-if="summary.generation_status === 'completed'">
                                    <p v-if="summary.content.ringkasan" class="text-body leading-7 text-foreground">
                                        {{ summary.content.ringkasan }}
                                    </p>

                                    <ul v-if="summary.content.poin?.length" class="space-y-2">
                                        <li v-for="(point, index) in summary.content.poin" :key="index" class="flex gap-2.5 text-sm leading-relaxed">
                                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true" />
                                            {{ point }}
                                        </li>
                                    </ul>

                                    <div v-if="summary.content.keputusan?.length" class="space-y-2">
                                        <p class="text-xs font-semibold text-ai-foreground">Keputusan</p>
                                        <ul class="space-y-1.5">
                                            <li
                                                v-for="(decision, index) in summary.content.keputusan"
                                                :key="index"
                                                class="flex gap-2.5 text-sm leading-relaxed"
                                            >
                                                <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true" />
                                                {{ decision }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div v-if="summary.content.action_items?.length" class="space-y-2">
                                        <p class="text-xs font-semibold text-ai-foreground">Usulan action item</p>
                                        <ul class="space-y-1.5">
                                            <li
                                                v-for="(item, index) in summary.content.action_items"
                                                :key="index"
                                                class="flex gap-2.5 text-sm leading-relaxed"
                                            >
                                                <span class="mt-2 size-1.5 shrink-0 rounded-full bg-ai" aria-hidden="true" />
                                                {{ item.title }}
                                            </li>
                                        </ul>
                                    </div>
                                </template>

                                <div class="flex flex-wrap items-center gap-2 border-t border-ai-border/60 pt-3">
                                    <Button
                                        v-if="summary.generation_status === 'completed'"
                                        size="sm"
                                        variant="outline"
                                        @click="copySummary(summary)"
                                    >
                                        <Copy class="size-3.5" aria-hidden="true" />
                                        Salin
                                    </Button>
                                    <template v-if="summary.status === 'draft' && summary.generation_status === 'completed' && canManage">
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
                                    </template>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Action item</CardTitle>
                                <CardDescription class="mt-1">PIC dan tenggat wajib sebelum finalisasi.</CardDescription>
                            </div>
                            <Badge variant="neutral">{{ props.meeting.action_items.length }} item</Badge>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <form
                                v-if="canManage && props.meeting.status === 'draft'"
                                class="grid gap-3 rounded-md border border-border/70 bg-muted/20 p-3 md:grid-cols-6"
                                @submit.prevent="addTask"
                            >
                                <div class="space-y-1 md:col-span-2">
                                    <Label for="new-task-title">Tugas</Label>
                                    <Input id="new-task-title" v-model="taskForm.title" required placeholder="Tugas baru…" />
                                </div>
                                <div class="space-y-1">
                                    <Label for="new-task-pic">PIC</Label>
                                    <Select id="new-task-pic" v-model="taskForm.pic_id">
                                        <option value="">Pilih PIC</option>
                                        <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">{{ person.name }}</option>
                                    </Select>
                                </div>
                                <div class="space-y-1">
                                    <Label for="new-task-division">Divisi</Label>
                                    <Select id="new-task-division" v-model="taskForm.division_id">
                                        <option value="">Pilih divisi</option>
                                        <option v-for="division in taskDivisions" :key="division.id" :value="String(division.id)">
                                            {{ division.name }}
                                        </option>
                                    </Select>
                                </div>
                                <div class="space-y-1">
                                    <Label for="new-task-due">Tenggat</Label>
                                    <Input id="new-task-due" v-model="taskForm.due_date" type="date" />
                                </div>
                                <div class="flex items-end">
                                    <Button type="submit" class="w-full">
                                        <Plus class="size-4" aria-hidden="true" />
                                        Tambah
                                    </Button>
                                </div>
                            </form>

                            <EmptyState v-if="props.meeting.action_items.length === 0" compact :icon="ListChecks" title="Belum ada action item" />
                            <div v-else class="hidden overflow-x-auto md:block">
                                <table class="w-full min-w-[900px] text-sm">
                                    <thead class="border-b text-left">
                                        <tr class="text-xs font-medium text-subtle-foreground">
                                            <th class="py-2.5 pr-3">Tugas</th>
                                            <th class="py-2.5 pr-3">PIC</th>
                                            <th class="py-2.5 pr-3">Tenggat</th>
                                            <th class="py-2.5 pr-3">Respons</th>
                                            <th class="py-2.5 pr-3">Status</th>
                                            <th class="py-2.5">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/60">
                                        <tr v-for="task in props.meeting.action_items" :key="task.id" class="transition-colors hover:bg-muted/20">
                                            <td class="py-3 pr-3">
                                                <template v-if="editingTaskId === task.id">
                                                    <Input v-model="editTaskForm.title" aria-label="Judul tugas" />
                                                </template>
                                                <template v-else>
                                                    <p class="font-medium leading-snug">{{ task.title }}</p>
                                                    <p v-if="task.description" class="mt-0.5 text-xs leading-relaxed text-muted-foreground">
                                                        {{ task.description }}
                                                    </p>
                                                    <Button size="sm" variant="ghost" class="mt-1 h-7 px-2 text-xs" @click="openFeedback(task)">
                                                        <MessageSquare class="size-3.5" aria-hidden="true" />
                                                        {{ task.feedback?.length ?? 0 }} respons
                                                    </Button>
                                                </template>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <Select
                                                    v-if="editingTaskId === task.id"
                                                    v-model="editTaskForm.pic_id"
                                                    class="h-8 text-xs"
                                                    aria-label="PIC"
                                                >
                                                    <option value="">-</option>
                                                    <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">
                                                        {{ person.name }}
                                                    </option>
                                                </Select>
                                                <span v-else class="text-sm">{{
                                                    task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada owner'
                                                }}</span>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <Input
                                                    v-if="editingTaskId === task.id"
                                                    v-model="editTaskForm.due_date"
                                                    type="date"
                                                    class="h-8 text-xs"
                                                    aria-label="Tenggat"
                                                />
                                                <div v-else class="space-y-1">
                                                    <span :class="task.is_overdue ? 'font-medium text-destructive-strong' : ''">{{
                                                        formatDate(task.due_date)
                                                    }}</span>
                                                    <Badge v-if="task.is_overdue" variant="danger" class="gap-1">
                                                        <AlertTriangle class="size-3" aria-hidden="true" />
                                                        Terlambat {{ task.overdue_hours ?? 0 }} jam
                                                    </Badge>
                                                </div>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <span
                                                    v-if="task.response_time_hours !== null && task.response_time_hours !== undefined"
                                                    class="text-xs font-medium"
                                                >
                                                    {{ task.response_time_hours }} jam
                                                </span>
                                                <span v-else class="text-xs text-muted-foreground">Belum ada</span>
                                            </td>
                                            <td class="py-3 pr-3">
                                                <Select
                                                    v-if="canEditTask(task) && editingTaskId !== task.id"
                                                    :model-value="task.status"
                                                    class="h-8 text-xs"
                                                    aria-label="Status tugas"
                                                    @update:model-value="updateTaskStatus(task, $event)"
                                                >
                                                    <option value="new">Baru</option>
                                                    <option value="in_progress">Berjalan</option>
                                                    <option value="done">Selesai</option>
                                                    <option value="cancelled">Dibatalkan</option>
                                                </Select>
                                                <Select
                                                    v-else-if="editingTaskId === task.id"
                                                    v-model="editTaskForm.status"
                                                    class="h-8 text-xs"
                                                    aria-label="Status tugas"
                                                >
                                                    <option value="new">Baru</option>
                                                    <option value="in_progress">Berjalan</option>
                                                    <option value="done">Selesai</option>
                                                    <option value="cancelled">Dibatalkan</option>
                                                </Select>
                                                <StatusBadge v-else kind="action" :value="task.status" />
                                            </td>
                                            <td class="py-3">
                                                <div v-if="canManage" class="flex items-center gap-1">
                                                    <template v-if="editingTaskId === task.id">
                                                        <Button size="sm" @click="saveEditTask(task)">Simpan</Button>
                                                        <Button size="sm" variant="ghost" aria-label="Batal menyunting" @click="editingTaskId = null">
                                                            <X class="size-4" aria-hidden="true" />
                                                        </Button>
                                                    </template>
                                                    <template v-else>
                                                        <Button size="sm" variant="ghost" aria-label="Sunting tugas" @click="startEditTask(task)">
                                                            <Pencil class="size-4" aria-hidden="true" />
                                                        </Button>
                                                        <Button size="sm" variant="ghost" aria-label="Hapus tugas" @click="deleteTask(task)">
                                                            <Trash2 class="size-4" aria-hidden="true" />
                                                        </Button>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="props.meeting.action_items.length > 0" class="space-y-2 md:hidden">
                                <div v-for="task in props.meeting.action_items" :key="task.id" class="rounded-md border border-border/70 p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <Input v-if="editingTaskId === task.id" v-model="editTaskForm.title" aria-label="Judul tugas" />
                                            <template v-else>
                                                <p class="font-medium leading-snug">{{ task.title }}</p>
                                                <p v-if="task.description" class="mt-0.5 text-xs leading-relaxed text-muted-foreground">
                                                    {{ task.description }}
                                                </p>
                                            </template>
                                        </div>
                                        <div v-if="canManage" class="flex items-center gap-0.5">
                                            <Button
                                                v-if="editingTaskId === task.id"
                                                size="sm"
                                                variant="ghost"
                                                aria-label="Batal menyunting"
                                                @click="editingTaskId = null"
                                            >
                                                <X class="size-4" aria-hidden="true" />
                                            </Button>
                                            <template v-else>
                                                <Button size="sm" variant="ghost" aria-label="Sunting tugas" @click="startEditTask(task)">
                                                    <Pencil class="size-4" aria-hidden="true" />
                                                </Button>
                                                <Button size="sm" variant="ghost" aria-label="Hapus tugas" @click="deleteTask(task)">
                                                    <Trash2 class="size-4" aria-hidden="true" />
                                                </Button>
                                            </template>
                                        </div>
                                    </div>

                                    <div v-if="editingTaskId === task.id" class="mt-3 grid grid-cols-2 gap-2">
                                        <div class="space-y-1">
                                            <Label>PIC</Label>
                                            <Select v-model="editTaskForm.pic_id" class="h-8 text-xs" aria-label="PIC">
                                                <option value="">-</option>
                                                <option v-for="person in taskUsers" :key="person.id" :value="String(person.id)">
                                                    {{ person.name }}
                                                </option>
                                            </Select>
                                        </div>
                                        <div class="space-y-1">
                                            <Label>Divisi</Label>
                                            <Select v-model="editTaskForm.division_id" class="h-8 text-xs" aria-label="Divisi">
                                                <option value="">-</option>
                                                <option v-for="division in taskDivisions" :key="division.id" :value="String(division.id)">
                                                    {{ division.name }}
                                                </option>
                                            </Select>
                                        </div>
                                        <div class="space-y-1">
                                            <Label>Tenggat</Label>
                                            <Input v-model="editTaskForm.due_date" type="date" class="h-8 text-xs" aria-label="Tenggat" />
                                        </div>
                                        <div class="space-y-1">
                                            <Label>Status</Label>
                                            <Select v-model="editTaskForm.status" class="h-8 text-xs" aria-label="Status tugas">
                                                <option value="new">Baru</option>
                                                <option value="in_progress">Berjalan</option>
                                                <option value="done">Selesai</option>
                                                <option value="cancelled">Dibatalkan</option>
                                            </Select>
                                        </div>
                                    </div>

                                    <div v-else class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <p class="text-muted-foreground">PIC / owner</p>
                                            <p class="font-medium">
                                                {{ task.pic?.name ?? task.pic_name ?? task.division?.name ?? 'Belum ada owner' }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-muted-foreground">Status</p>
                                            <Select
                                                v-if="canEditTask(task)"
                                                :model-value="task.status"
                                                class="h-8 text-xs"
                                                aria-label="Status tugas"
                                                @update:model-value="updateTaskStatus(task, $event)"
                                            >
                                                <option value="new">Baru</option>
                                                <option value="in_progress">Berjalan</option>
                                                <option value="done">Selesai</option>
                                                <option value="cancelled">Dibatalkan</option>
                                            </Select>
                                            <StatusBadge v-else kind="action" :value="task.status" />
                                        </div>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-end justify-between gap-2">
                                        <div class="space-y-1">
                                            <p class="text-xs text-muted-foreground">Tenggat</p>
                                            <p :class="task.is_overdue ? 'text-xs font-medium text-destructive-strong' : 'text-xs'">
                                                {{ formatDate(task.due_date) }}
                                            </p>
                                            <Badge v-if="task.is_overdue" variant="danger" class="mt-1 gap-1">
                                                <AlertTriangle class="size-3" aria-hidden="true" />
                                                Terlambat {{ task.overdue_hours ?? 0 }} jam
                                            </Badge>
                                            <p class="text-xs text-muted-foreground">
                                                Respons:
                                                {{
                                                    task.response_time_hours !== null && task.response_time_hours !== undefined
                                                        ? `${task.response_time_hours} jam`
                                                        : 'Belum ada'
                                                }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Button size="sm" variant="outline" @click="openFeedback(task)">
                                                <MessageSquare class="size-3.5" aria-hidden="true" />
                                                {{ task.feedback?.length ?? 0 }} respons
                                            </Button>
                                            <Button v-if="editingTaskId === task.id" size="sm" @click="saveEditTask(task)">Simpan</Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Lampiran</CardTitle>
                            <CardDescription>Berkas pendukung dan tautan terkait dokumen.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <form
                                v-if="canManage"
                                class="space-y-3 rounded-md border border-border/70 bg-muted/20 p-3"
                                @submit.prevent="uploadAttachment"
                            >
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <Label for="attachment-file">Berkas</Label>
                                        <input
                                            id="attachment-file"
                                            type="file"
                                            class="block w-full text-xs text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                                            @change="onFileChange"
                                        />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label for="attachment-url">Atau tautan</Label>
                                        <Input id="attachment-url" v-model="attachmentForm.url" type="url" placeholder="https://…" />
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <div class="flex-1 space-y-1.5">
                                        <Label for="attachment-name">Nama lampiran (opsional)</Label>
                                        <Input id="attachment-name" v-model="attachmentForm.name" placeholder="Nama lampiran" />
                                    </div>
                                    <Button type="submit" size="sm">
                                        <FileUp class="size-4" aria-hidden="true" />
                                        Unggah
                                    </Button>
                                </div>
                            </form>

                            <EmptyState v-if="props.meeting.attachments.length === 0" compact :icon="Paperclip" title="Belum ada lampiran" />
                            <div v-else class="space-y-2">
                                <div
                                    v-for="attachment in props.meeting.attachments"
                                    :key="attachment.id"
                                    class="flex items-center justify-between gap-3 rounded-md border border-border/70 p-2.5"
                                >
                                    <a
                                        :href="attachmentUrl(attachment)"
                                        target="_blank"
                                        rel="noopener"
                                        class="flex min-w-0 items-center gap-2 hover:text-primary"
                                    >
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                            <Paperclip class="size-4" aria-hidden="true" />
                                        </span>
                                        <span class="truncate text-sm">{{ attachment.original_name }}</span>
                                    </a>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <Badge variant="outline">{{ attachment.kind }}</Badge>
                                        <Button
                                            v-if="canManage"
                                            size="sm"
                                            variant="ghost"
                                            aria-label="Hapus lampiran"
                                            @click="deleteAttachment(attachment)"
                                        >
                                            <Trash2 class="size-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4 lg:sticky lg:top-20">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ props.meeting.document_type === 'meeting' ? 'Informasi rapat' : 'Informasi dokumen' }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-start gap-2.5">
                                <CalendarClock class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ props.meeting.document_type === 'meeting' ? 'Jadwal' : 'Tanggal' }}
                                    </p>
                                    <p class="font-medium">{{ formatDateTime(props.meeting.scheduled_at) }}</p>
                                </div>
                            </div>
                            <div v-if="props.meeting.location" class="flex items-start gap-2.5">
                                <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Lokasi</p>
                                    <p class="font-medium">{{ props.meeting.location }}</p>
                                </div>
                            </div>
                            <div v-if="props.meeting.meeting_link" class="flex items-start gap-2.5">
                                <ExternalLink class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div class="min-w-0">
                                    <p class="text-xs text-muted-foreground">Tautan rapat</p>
                                    <a
                                        :href="props.meeting.meeting_link"
                                        target="_blank"
                                        rel="noopener"
                                        class="truncate font-medium text-primary hover:underline"
                                    >
                                        Buka tautan
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <UserRound class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ props.meeting.document_type === 'meeting' ? 'Notulis' : 'Pembuat' }}
                                    </p>
                                    <p class="font-medium">{{ props.meeting.creator?.name ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <Building2 class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Divisi</p>
                                    <p class="font-medium">{{ props.meeting.division?.name ?? 'Tanpa divisi' }}</p>
                                </div>
                            </div>
                            <div v-if="props.meeting.template" class="flex items-start gap-2.5">
                                <FileText class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Template</p>
                                    <p class="font-medium">{{ props.meeting.template.name }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle>Peserta</CardTitle>
                            <Badge variant="neutral">{{ props.meeting.participants.length }}</Badge>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <AvatarGroup v-if="participantUsers.length" :users="participantUsers" :max="5" />
                            <p v-if="props.meeting.participants.length === 0" class="text-sm text-muted-foreground">Belum ada peserta.</p>
                            <div v-else class="space-y-1">
                                <div
                                    v-for="participant in props.meeting.participants"
                                    :key="participant.id"
                                    class="flex items-center justify-between gap-2 rounded-md px-1 py-1.5"
                                >
                                    <div class="flex min-w-0 items-center gap-2">
                                        <Avatar size="xs">
                                            <AvatarFallback class="bg-secondary text-secondary-foreground">
                                                {{ getInitials(participant.user?.name ?? participant.name ?? participant.email ?? 'P') }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <Link
                                            v-if="participant.user?.id"
                                            :href="route('people.show', participant.user.id)"
                                            class="truncate text-sm hover:text-primary hover:underline"
                                        >
                                            {{ participant.user.name }}
                                        </Link>
                                        <span v-else class="truncate text-sm">{{ participant.name ?? participant.email }}</span>
                                    </div>
                                    <AttendanceBadge :value="participant.attendance" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tag</CardTitle>
                            <CardDescription>Orang, divisi, dan topik yang terkait.</CardDescription>
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
                            <p v-else class="text-sm text-muted-foreground">Belum ada tag.</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Riwayat versi</CardTitle>
                            <CardDescription>Snapshot isi dokumen dari waktu ke waktu.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <EmptyState v-if="props.meeting.versions.length === 0" compact :icon="History" title="Belum ada versi" />
                            <div v-else class="relative space-y-4 before:absolute before:inset-y-1 before:left-[5px] before:w-px before:bg-border">
                                <div v-for="version in props.meeting.versions" :key="version.id" class="relative flex gap-3">
                                    <span
                                        class="relative z-10 mt-1.5 size-2.5 shrink-0 rounded-full border-2 border-card bg-primary"
                                        aria-hidden="true"
                                    />
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-medium">Versi {{ version.version }}</p>
                                            <span class="text-xs text-muted-foreground">{{ formatDateTime(version.created_at) }}</span>
                                        </div>
                                        <p class="text-xs leading-relaxed text-muted-foreground">{{ version.reason }}</p>
                                        <Button v-if="canManage" size="sm" variant="ghost" @click="restoreVersion(version)">Pulihkan</Button>
                                    </div>
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
                        <Label for="share-recipients">Email penerima</Label>
                        <Textarea
                            id="share-recipients"
                            v-model="shareForm.recipients"
                            rows="2"
                            placeholder="nama@perusahaan.com, rekan@perusahaan.com"
                            :aria-invalid="Boolean(shareForm.errors.recipients)"
                        />
                        <p class="text-xs text-muted-foreground">Pisahkan dengan koma. Maksimal 10 penerima.</p>
                        <p v-if="shareForm.errors.recipients" class="text-xs font-medium text-destructive-strong">
                            {{ shareForm.errors.recipients }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="share-message">Pesan (opsional)</Label>
                        <Textarea id="share-message" v-model="shareForm.message" rows="3" placeholder="Tambahkan catatan untuk penerima…" />
                        <p v-if="shareForm.errors.message" class="text-xs font-medium text-destructive-strong">{{ shareForm.errors.message }}</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input v-model="shareForm.include_pdf" type="checkbox" class="size-4 rounded border-input accent-primary" />
                        Lampirkan salinan PDF
                    </label>

                    <div
                        v-if="requiresShareConfirmation"
                        class="space-y-2 rounded-md border border-warning/30 bg-warning-soft p-3 text-xs text-warning-strong"
                    >
                        <p>
                            Dokumen ini berlabel <strong>{{ confidentialityLabels[props.meeting.confidentiality] }}</strong
                            >. Pastikan seluruh penerima berhak menerima dokumen.
                        </p>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input v-model="shareForm.confirm_share" type="checkbox" class="size-4 rounded border-input accent-primary" />
                            Saya mengonfirmasi penerima berhak.
                        </label>
                        <p v-if="shareForm.errors.confirm_share">{{ shareForm.errors.confirm_share }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="shareOpen = false">Batal</Button>
                        <Button type="submit" :loading="shareForm.processing">
                            <Share2 class="size-4" aria-hidden="true" />
                            Kirim email
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="confirmState.open"
            :title="confirmState.title"
            :description="confirmState.description"
            :confirm-label="confirmState.confirmLabel"
            :variant="confirmState.variant"
            @update:open="confirmState.open = $event"
            @confirm="runConfirmedAction"
        />

        <ActionItemFeedbackDialog
            :open="feedbackTaskId !== null"
            :task="selectedFeedbackTask"
            :can-respond="selectedFeedbackTask ? canEditTask(selectedFeedbackTask) : false"
            @update:open="
                (open) => {
                    if (!open) feedbackTaskId = null;
                }
            "
        />
    </AppLayout>
</template>
