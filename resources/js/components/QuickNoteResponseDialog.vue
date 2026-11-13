<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/labels';
import type { QuickNoteMention, QuickNoteResponseStatus } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    noteId: number;
    mention: QuickNoteMention | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const options: { value: QuickNoteResponseStatus; label: string }[] = [
    { value: 'acknowledged', label: 'Diterima' },
    { value: 'scheduled', label: 'Akan diagendakan' },
    { value: 'in_progress', label: 'Sedang ditindaklanjuti' },
    { value: 'blocked', label: 'Terhambat' },
    { value: 'done', label: 'Selesai' },
    { value: 'need_help', label: 'Butuh bantuan' },
];

const form = useForm({
    response_status: 'acknowledged' as QuickNoteResponseStatus,
    response_body: '',
});

const name = computed(() => props.mention?.user?.name ?? 'Pengguna');

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.response_status = props.mention?.response_status ?? 'acknowledged';
            form.response_body = props.mention?.response_body ?? '';
            form.clearErrors();
        }
    },
);

function submit() {
    if (!props.mention) {
        return;
    }

    form.post(route('quick-notes.respond', props.noteId), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <form class="space-y-4" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Respons untuk @{{ name }}</DialogTitle>
                    <DialogDescription>
                        Berikan status dan balasan singkat. Batas respons
                        <template v-if="props.mention?.due_at"> sampai {{ formatDateTime(props.mention.due_at) }}</template>
                        ({{ props.mention?.response_hours ?? 24 }} jam).
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-1.5">
                    <Label for="quick-note-response-status">Status respons</Label>
                    <Select id="quick-note-response-status" v-model="form.response_status">
                        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </Select>
                    <p v-if="form.errors.response_status" class="text-xs font-medium text-destructive" role="alert">
                        {{ form.errors.response_status }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="quick-note-response-body">Balasan singkat</Label>
                    <Textarea
                        id="quick-note-response-body"
                        v-model="form.response_body"
                        rows="3"
                        placeholder="Contoh: Sudah saya baca, akan saya masukkan ke agenda minggu ini."
                        :aria-invalid="Boolean(form.errors.response_body)"
                        :aria-describedby="form.errors.response_body ? 'quick-note-response-error' : undefined"
                    />
                    <p v-if="form.errors.response_body" id="quick-note-response-error" class="text-xs font-medium text-destructive" role="alert">
                        {{ form.errors.response_body }}
                    </p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Batal</Button>
                    <Button type="submit" :loading="form.processing">Kirim respons</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
