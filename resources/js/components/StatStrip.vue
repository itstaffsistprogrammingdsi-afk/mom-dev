<script setup lang="ts">
interface StatItem {
    label: string;
    value: number | string;
    hint?: string;
    tone?: 'default' | 'warning' | 'danger' | 'success';
}

defineProps<{
    items: StatItem[];
}>();

const toneClasses: Record<string, string> = {
    default: 'text-foreground',
    warning: 'text-warning-strong',
    danger: 'text-destructive-strong',
    success: 'text-success-strong',
};
</script>

<template>
    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border/80 bg-border/70 sm:grid-cols-4">
        <div v-for="item in items" :key="item.label" class="bg-surface px-4 py-3.5">
            <p class="text-[11px] font-medium text-muted-foreground">{{ item.label }}</p>
            <p class="mt-1 text-xl font-semibold leading-none tabular-nums tracking-[-0.02em]" :class="toneClasses[item.tone ?? 'default']">
                {{ item.value }}
            </p>
            <p v-if="item.hint" class="mt-1 text-[11px] text-muted-foreground">{{ item.hint }}</p>
        </div>
    </div>
</template>
