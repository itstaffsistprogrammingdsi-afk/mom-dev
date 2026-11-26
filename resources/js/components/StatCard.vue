<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import type { Component } from 'vue';

interface Props {
    label: string;
    value: number | string;
    hint?: string;
    icon?: Component;
    tone?: 'default' | 'warning' | 'danger' | 'success';
}

withDefaults(defineProps<Props>(), {
    tone: 'default',
});

const toneClasses: Record<string, string> = {
    default: 'text-muted-foreground',
    warning: 'text-warning-strong',
    danger: 'text-destructive-strong',
    success: 'text-success-strong',
};
</script>

<template>
    <Card class="border-border/80 shadow-none">
        <CardContent class="p-4">
            <div class="flex items-center gap-2">
                <component :is="icon" v-if="icon" class="size-3.5 shrink-0" :class="toneClasses[tone]" aria-hidden="true" />
                <p class="text-xs font-medium text-muted-foreground">{{ label }}</p>
            </div>
            <p class="mt-2 text-2xl font-semibold leading-none tabular-nums tracking-[-0.02em] text-foreground">{{ value }}</p>
            <p v-if="hint" class="mt-1.5 text-xs leading-relaxed text-muted-foreground">{{ hint }}</p>
        </CardContent>
    </Card>
</template>
