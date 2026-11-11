<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Component, HTMLAttributes } from 'vue';

interface Props {
    title: string;
    description?: string;
    icon?: Component;
    compact?: boolean;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
});
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border bg-muted/20 px-6 text-center',
                compact ? 'py-8' : 'py-14',
                props.class,
            )
        "
    >
        <div
            v-if="icon"
            class="flex size-11 items-center justify-center rounded-full bg-surface text-muted-foreground shadow-soft-xs ring-1 ring-border/70"
        >
            <component :is="icon" class="size-5" aria-hidden="true" />
        </div>

        <div class="space-y-1">
            <p class="text-sm font-medium text-foreground">{{ title }}</p>
            <p v-if="description" class="mx-auto max-w-md text-sm leading-relaxed text-muted-foreground">{{ description }}</p>
        </div>

        <div v-if="$slots.default" class="pt-1">
            <slot />
        </div>
    </div>
</template>
