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
    default: 'bg-primary/10 text-primary',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    danger: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
};
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-3 p-5">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p class="text-3xl font-semibold tracking-tight">{{ value }}</p>
                <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
            </div>
            <div v-if="icon" class="rounded-lg p-2" :class="toneClasses[tone]">
                <component :is="icon" class="size-5" />
            </div>
        </CardContent>
    </Card>
</template>
