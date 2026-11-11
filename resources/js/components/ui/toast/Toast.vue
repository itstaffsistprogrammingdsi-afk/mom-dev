<script setup lang="ts">
import type { ToastItem } from '@/composables/useToast';
import { cn } from '@/lib/utils';
import { CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = defineProps<{
    toast: ToastItem;
}>();

const emit = defineEmits<{
    close: [];
}>();

const icons: Record<ToastItem['variant'], Component> = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
    neutral: Info,
};

const variantClasses: Record<ToastItem['variant'], string> = {
    success: 'border-success/25 bg-success-soft text-success-strong',
    error: 'border-destructive/25 bg-destructive-soft text-destructive-strong',
    info: 'border-info/25 bg-info-soft text-info-strong',
    neutral: 'border-border bg-surface text-surface-foreground',
};

function runAction() {
    props.toast.onAction?.();
    emit('close');
}
</script>

<template>
    <div
        :role="props.toast.variant === 'error' ? 'alert' : 'status'"
        :class="
            cn(
                'pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border p-4 shadow-popover',
                variantClasses[props.toast.variant],
            )
        "
    >
        <component :is="icons[props.toast.variant]" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />

        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium leading-snug">{{ props.toast.title }}</p>
            <p v-if="props.toast.description" class="mt-1 text-sm leading-snug opacity-80">{{ props.toast.description }}</p>
        </div>

        <button
            v-if="props.toast.actionLabel && props.toast.onAction"
            type="button"
            class="shrink-0 self-center text-sm font-medium underline underline-offset-2 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current"
            @click="runAction"
        >
            {{ props.toast.actionLabel }}
        </button>

        <button
            type="button"
            class="rounded-md p-0.5 opacity-70 transition-opacity duration-fast ease-out-soft hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current"
            aria-label="Tutup notifikasi"
            @click="emit('close')"
        >
            <X class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
