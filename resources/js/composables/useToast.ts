import { reactive, readonly } from 'vue';

export type ToastVariant = 'success' | 'error' | 'info' | 'neutral';

export interface ToastItem {
    id: number;
    variant: ToastVariant;
    title: string;
    description?: string;
    duration: number;
    actionLabel?: string;
    onAction?: () => void;
}

interface ToastInput {
    title: string;
    description?: string;
    variant?: ToastVariant;
    duration?: number;
    actionLabel?: string;
    onAction?: () => void;
}

const toasts = reactive<ToastItem[]>([]);
const timers = new Map<number, ReturnType<typeof setTimeout>>();
let nextId = 1;

export function dismissToast(id: number) {
    const timer = timers.get(id);

    if (timer) {
        clearTimeout(timer);
        timers.delete(id);
    }

    const index = toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

export function pushToast(input: ToastInput): number {
    const id = nextId++;
    const duration = input.duration ?? 5000;

    while (toasts.length >= 3) {
        dismissToast(toasts[0].id);
    }

    toasts.push({
        id,
        variant: input.variant ?? 'neutral',
        title: input.title,
        description: input.description,
        duration,
        actionLabel: input.actionLabel,
        onAction: input.onAction,
    });

    if (duration > 0) {
        timers.set(
            id,
            setTimeout(() => dismissToast(id), duration),
        );
    }

    return id;
}

export function useToast() {
    return {
        toasts: readonly(toasts),
        dismiss: dismissToast,
        push: pushToast,
        success: (title: string, description?: string) => pushToast({ title, description, variant: 'success' }),
        error: (title: string, description?: string) => pushToast({ title, description, variant: 'error', duration: 7000 }),
        info: (title: string, description?: string) => pushToast({ title, description, variant: 'info' }),
    };
}
