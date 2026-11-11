<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/labels';
import type { ActionItem, ActionItemFeedback } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    open: boolean;
    task: ActionItem | null;
    canRespond: boolean;
}>();

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

const quickReplies = [
    { label: 'Diterima', status: 'acknowledged' as const, body: 'Baik, tugas sudah saya terima.' },
    { label: 'Akan diagendakan', status: 'scheduled' as const, body: 'Baik, akan diagendakan segera.' },
    { label: 'Sedang dikerjakan', status: 'in_progress' as const, body: 'Sedang saya kerjakan, akan saya update bila ada progres.' },
    { label: 'Terhambat', status: 'blocked' as const, body: 'Saat ini terhambat, saya akan sampaikan kendalanya.' },
    { label: 'Selesai', status: 'done' as const, body: 'Sudah selesai, mohon dicek.' },
    { label: 'Butuh bantuan', status: 'need_help' as const, body: 'Saya butuh bantuan untuk menyelesaikan tugas ini.' },
];

const form = useForm({
    body: '',
    response_status: 'acknowledged' as ActionItemFeedback['response_status'],
});

const numberFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });

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
    form.reset('body');
    form.response_status = 'acknowledged';
}

function submit() {
    if (!props.task || !form.body.trim()) {
        return;
    }

    form.post(route('action-items.feedback.store', props.task.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: resetForm,
    });
}

watch(
    () => props.task?.id,
    () => resetForm(),
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
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Owner</p>
                        <p class="font-medium">{{ props.task.is_owned ? 'Sudah ada' : 'Belum ada' }}</p>
                    </div>
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Response time</p>
                        <p class="font-medium">{{ formatHours(props.task.response_time_hours) }}</p>
                    </div>
                    <div class="rounded-md bg-muted/40 p-2.5">
                        <p class="text-muted-foreground">Terlambat</p>
                        <p class="font-medium">{{ formatHours(props.task.overdue_hours) }}</p>
                    </div>
                </div>

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
                        </div>
                    </div>
                </div>

                <form v-if="props.canRespond" class="space-y-3 border-t border-border/60 pt-4" @submit.prevent="submit">
                    <div class="space-y-1.5">
                        <Label for="feedback-status">Keterangan respons</Label>
                        <Select id="feedback-status" v-model="form.response_status">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </Select>
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
