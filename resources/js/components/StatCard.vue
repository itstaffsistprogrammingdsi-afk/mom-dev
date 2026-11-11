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
    default: 'bg-accent text-accent-foreground',
    warning: 'bg-warning-soft text-warning-strong',
    danger: 'bg-destructive-soft text-destructive-strong',
    success: 'bg-success-soft text-success-strong',
};
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-3 p-card">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p class="text-3xl font-semibold tabular-nums tracking-tight text-foreground">{{ value }}</p>
                <p v-if="hint" class="text-xs leading-relaxed text-muted-foreground">{{ hint }}</p>
            </div>
            <div v-if="icon" class="rounded-md p-2" :class="toneClasses[tone]">
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
        </CardContent>
    </Card>
</template>
