<script setup lang="ts">
import PhotoAttachmentField from '@/components/PhotoAttachmentField.vue';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/labels';
import type { ActionItem, ActionItemFeedback } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Clock3, ImageIcon, Info } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        task: ActionItem | null;
        canRespond: boolean;
        pendingStatus?: string | null;
    }>(),
    {
        pendingStatus: null,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const statusOptions: { value: ActionItemFeedback['response_status']; label: string; variant: BadgeVariants['variant'] }[] = [
    { value: 'acknowledged', label: 'Diterima', variant: 'info' },
    { value: 'scheduled', label: 'Akan diagendakan', variant: 'info' },
    { value: 'in_progress', label: 'Sedang dikerjakan', variant: 'warning' },
    { value: 'blocked', label: 'Terhambat', variant: 'danger' },
    { value: 'done', label: 'Selesai', variant: 'success' },
    { value: 'need_help', label: 'Butuh bantuan', variant: 'warning' },
];

const responseOptions = [
    { value: 1, label: '1 jam' },
    { value: 4, label: '4 jam' },
    { value: 24, label: '24 jam' },
    { value: 48, label: '48 jam' },
];

const quickReplies = [
    { label: 'Diterima', status: 'acknowledged' as const, body: 'Baik, tugas sudah saya terima.' },
    { label: 'Akan diagendakan', status: 'scheduled' as const, body: 'Baik, akan diagendakan segera.' },
    { label: 'Sedang dikerjakan', status: 'in_progress' as const, body: 'Sedang saya kerjakan, akan saya update bila ada progres.' },
    { label: 'Terhambat', status: 'blocked' as const, body: 'Saat ini terhambat, saya akan sampaikan kendalanya.' },
    { label: 'Selesai', status: 'done' as const, body: 'Sudah selesai, mohon dicek.' },
    { label: 'Butuh bantuan', status: 'need_help' as const, body: 'Saya butuh bantuan untuk menyelesaikan tugas ini.' },
];

const form = useForm<{
    body: string;
    response_status: ActionItemFeedback['response_status'];
    status: string;
    response_hours: number;
    photo: File | null;
}>({
    body: '',
    response_status: 'acknowledged',
    status: '',
    response_hours: 24,
    photo: null,
});

const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });

const showResponseHours = computed(() => props.canRespond && props.task !== null && !props.task.response_due_at);
const responseDueSoon = computed(() => {
    if (!props.task?.response_due_at) {
        return false;
    }

    return !props.task.is_response_overdue && new Date(props.task.response_due_at).getTime() - Date.now() < 4 * 60 * 60 * 1000;
});

function formatHours(value: number | null | undefined): string {
    return value === null || value === undefined ? '-' : `${numberFormat.format(value)} jam`;
}

function variantFor(status: ActionItemFeedback['response_status']): BadgeVariants['variant'] {
    return statusOptions.find((option) => option.value === status)?.variant ?? 'neutral';
}

function applyQuickReply(reply: (typeof quickReplies)[number]) {
    form.response_status = reply.status;
    form.body = reply.body;
}

function resetForm() {
    form.body = '';
    form.response_status = props.pendingStatus === 'in_progress' ? 'in_progress' : 'acknowledged';
    form.status = props.pendingStatus ?? '';
    form.response_hours = props.task?.response_hours ?? 24;
    form.photo = null;
    form.clearErrors();
}

function submit() {
    if (!props.task || !form.body.trim()) {
        return;
    }

    form.post(route('action-items.feedback.store', props.task.id), {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            emit('update:open', false);
            resetForm();
        },
    });
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

watch(
    () => props.task?.id,
    () => {
        if (props.open) {
            resetForm();
        }
    },
);
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Respons dan feedback</DialogTitle>
                <DialogDescription>{{ props.task?.title ?? 'Pilih action item untuk melihat respons.' }}</DialogDescription>
            </DialogHeader>

            <div v-if="props.task" class="space-y-4">
                <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Owner</p>
                        <p class="font-medium">{{ props.task.is_owned ? 'Sudah ada' : 'Belum ada' }}</p>
                    </div>
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Response time</p>
                        <p class="font-medium">{{ formatHours(props.task.response_time_hours) }}</p>
                    </div>
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Batas respons</p>
                        <p
                            class="font-medium"
                            :class="props.task.is_response_overdue ? 'text-destructive-strong' : responseDueSoon ? 'text-warning-strong' : ''"
                        >
                            {{ props.task.response_due_at ? formatDateTime(props.task.response_due_at) : `${props.task.response_hours ?? 24} jam` }}
                        </p>
                    </div>
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Terlambat</p>
                        <p class="font-medium" :class="props.task.is_response_overdue ? 'text-destructive-strong' : ''">
                            {{ formatHours(props.task.response_overdue_hours) }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="props.task.is_response_overdue"
                    class="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-xs leading-5 text-destructive-strong"
                >
                    <Clock3 class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                    Batas respons sudah lewat {{ formatHours(props.task.response_overdue_hours) }}. Segera berikan respons.
                </p>

                <div class="space-y-2">
                    <p class="text-xs font-medium text-subtle-foreground">Riwayat respons</p>
                    <div
                        v-if="(props.task.feedback?.length ?? 0) === 0"
                        class="rounded-md border border-dashed border-border p-4 text-center text-sm text-muted-foreground"
                    >
                        Belum ada respons. PIC atau notulis dapat menambahkan feedback pertama.
                    </div>
                    <div v-else class="max-h-64 space-y-3 overflow-y-auto pr-1" aria-live="polite">
                        <div v-for="feedback in props.task.feedback" :key="feedback.id" class="space-y-2 rounded-md border border-border/70 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-medium">{{ feedback.user?.name ?? 'Pengguna' }}</p>
                                    <Badge :variant="variantFor(feedback.response_status)">
                                        {{ feedback.response_status_label }}
                                    </Badge>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ formatDateTime(feedback.created_at) }}</span>
                            </div>
                            <p class="text-sm leading-relaxed text-muted-foreground">{{ feedback.body }}</p>
                            <a
                                v-if="feedback.photo_url"
                                :href="feedback.photo_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-md border border-border bg-muted/30 p-2 text-xs font-medium text-primary hover:underline"
                            >
                                <img :src="feedback.photo_url" alt="Foto respons" class="size-14 rounded object-cover" />
                                <span class="inline-flex items-center gap-1">
                                    <ImageIcon class="size-3.5" aria-hidden="true" />
                                    {{ feedback.photo_original_name ?? 'Lihat foto' }}
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <form v-if="props.canRespond" class="space-y-3 border-t border-border/60 pt-4" @submit.prevent="submit">
                    <p class="flex items-start gap-2 rounded-md bg-muted/40 p-3 text-xs leading-5 text-muted-foreground">
                        <Info class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                        Hasil respons akan dikirim ke pemilik dokumen, peserta, dan anggota yang di-tag pada dokumen.
                    </p>

                    <div class="space-y-1.5">
                        <Label for="feedback-status">Keterangan respons</Label>
                        <Select id="feedback-status" v-model="form.response_status">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </Select>
                    </div>

                    <div v-if="showResponseHours" class="space-y-1.5">
                        <Label for="feedback-response-hours">Batas respons</Label>
                        <Select id="feedback-response-hours" v-model.number="form.response_hours">
                            <option v-for="option in responseOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </Select>
                        <p class="text-xs leading-5 text-muted-foreground">Batas mulai dihitung saat status tugas berubah menjadi berjalan.</p>
                    </div>

                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-subtle-foreground">Balasan cepat</p>
                        <div class="flex flex-wrap gap-1.5">
                            <Button
                                v-for="reply in quickReplies"
                                :key="reply.label"
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="applyQuickReply(reply)"
                            >
                                {{ reply.label }}
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="feedback-body">Pesan respons</Label>
                        <Textarea
                            id="feedback-body"
                            v-model="form.body"
                            rows="3"
                            placeholder="Tulis progres, kendala, atau feedback untuk tugas ini…"
                        />
                        <p v-if="form.errors.body" class="text-xs font-medium text-destructive-strong">{{ form.errors.body }}</p>
                        <p v-if="form.errors.response_status" class="text-xs font-medium text-destructive-strong">
                            {{ form.errors.response_status }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Foto pendukung</Label>
                        <PhotoAttachmentField v-model="form.photo" hint="Lampirkan foto bukti atau kondisi terkini. Maksimal 5 MB." />
                        <p v-if="form.errors.photo" class="text-xs font-medium text-destructive-strong">{{ form.errors.photo }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="emit('update:open', false)">Batal</Button>
                        <Button type="submit" :loading="form.processing" :disabled="!form.body.trim()">Kirim respons</Button>
                    </DialogFooter>
                </form>

                <p v-else class="border-t border-border/60 pt-4 text-xs leading-relaxed text-muted-foreground">
                    Hanya PIC terkait atau pengguna dengan akses notulis yang dapat menambahkan respons.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
