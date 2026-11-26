<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { MeetingSignatures } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Loader2, Trash2, Upload } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    meetingId: number;
    signatures: MeetingSignatures;
    creatorName: string;
    approverName: string;
    canSignCreator: boolean;
    canSignApprover: boolean;
}>();

const creatorInput = ref<HTMLInputElement | null>(null);
const approverInput = ref<HTMLInputElement | null>(null);

const creatorForm = useForm<{ signature: File | null }>({ signature: null });
const approverForm = useForm<{ signature: File | null }>({ signature: null });

function pick(
    form: { signature: File | null; post: (url: string, options: Record<string, unknown>) => void; reset: () => void },
    role: 'creator' | 'approver',
    inputRef: HTMLInputElement | null,
    event: Event,
): void {
    const input = event.target as HTMLInputElement;
    form.signature = input.files?.[0] ?? null;

    if (!form.signature) {
        return;
    }

    form.post(route('meeting-signatures.store', { meeting: props.meetingId, role }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (inputRef) {
                inputRef.value = '';
            }
        },
    });
}

function remove(role: 'creator' | 'approver'): void {
    router.delete(route('meeting-signatures.destroy', { meeting: props.meetingId, role }), { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-lg border border-border bg-muted/20 p-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">Notulis</p>
                    <p class="text-xs text-muted-foreground">{{ props.signatures.creator?.name ?? props.creatorName }}</p>
                </div>
                <span
                    v-if="props.signatures.creator"
                    class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success"
                >
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    Ditandatangani
                </span>
                <span v-else class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">Belum</span>
            </div>

            <div v-if="props.signatures.creator" class="mt-3 flex items-center gap-3">
                <img :src="props.signatures.creator.url" alt="Tanda tangan notulis" class="max-h-16 rounded border border-border bg-white p-1" />
                <Button variant="outline" size="sm" type="button" @click="remove('creator')">
                    <Trash2 class="size-3.5" aria-hidden="true" />
                    Hapus
                </Button>
            </div>

            <div v-else-if="props.canSignCreator" class="mt-3 flex flex-wrap items-center gap-2">
                <input
                    id="creator-signature-input"
                    ref="creatorInput"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="hidden"
                    aria-hidden="true"
                    tabindex="-1"
                    @change="pick(creatorForm, 'creator', creatorInput, $event)"
                />
                <Button variant="outline" size="sm" type="button" @click="creatorInput?.click()">
                    <Upload class="size-3.5" aria-hidden="true" />
                    Pilih tanda tangan
                </Button>
                <span v-if="creatorForm.processing" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Loader2 class="size-3.5 animate-spin" aria-hidden="true" />
                    Mengunggah...
                </span>
            </div>
            <p v-if="creatorForm.errors.signature" class="mt-2 text-xs font-medium text-destructive-strong">{{ creatorForm.errors.signature }}</p>
            <p v-if="!props.canSignCreator && !props.signatures.creator" class="mt-2 text-xs text-muted-foreground">
                Hanya notulis pembuat dokumen atau admin yang dapat menandatangani.
            </p>
        </div>

        <div class="rounded-lg border border-border bg-muted/20 p-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold">Pimpinan Rapat</p>
                    <p class="text-xs text-muted-foreground">{{ props.signatures.approver?.name ?? props.approverName }}</p>
                </div>
                <span
                    v-if="props.signatures.approver"
                    class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success"
                >
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    Ditandatangani
                </span>
                <span v-else class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">Belum</span>
            </div>

            <div v-if="props.signatures.approver" class="mt-3 flex items-center gap-3">
                <img :src="props.signatures.approver.url" alt="Tanda tangan pimpinan" class="max-h-16 rounded border border-border bg-white p-1" />
                <Button variant="outline" size="sm" type="button" @click="remove('approver')">
                    <Trash2 class="size-3.5" aria-hidden="true" />
                    Hapus
                </Button>
            </div>

            <div v-else-if="props.canSignApprover" class="mt-3 flex flex-wrap items-center gap-2">
                <input
                    id="approver-signature-input"
                    ref="approverInput"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    class="hidden"
                    aria-hidden="true"
                    tabindex="-1"
                    @change="pick(approverForm, 'approver', approverInput, $event)"
                />
                <Button variant="outline" size="sm" type="button" @click="approverInput?.click()">
                    <Upload class="size-3.5" aria-hidden="true" />
                    Pilih tanda tangan
                </Button>
                <span v-if="approverForm.processing" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Loader2 class="size-3.5 animate-spin" aria-hidden="true" />
                    Mengunggah...
                </span>
            </div>
            <p v-if="approverForm.errors.signature" class="mt-2 text-xs font-medium text-destructive-strong">{{ approverForm.errors.signature }}</p>
            <p v-if="!props.canSignApprover && !props.signatures.approver" class="mt-2 text-xs text-muted-foreground">
                Hanya pimpinan rapat atau admin yang dapat menandatangani.
            </p>
        </div>
    </div>
</template>
