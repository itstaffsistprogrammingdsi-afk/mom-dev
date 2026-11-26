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

const icon = props.icon;
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center gap-2 rounded-xl bg-muted/45 px-6 text-center',
                compact ? 'py-6' : 'py-10',
                props.class,
            )
        "
    >
        <component :is="icon" v-if="icon" class="size-5 text-muted-foreground" aria-hidden="true" />

        <div class="space-y-1">
            <p class="text-sm font-medium text-foreground">{{ title }}</p>
            <p v-if="description" class="mx-auto max-w-md text-sm leading-relaxed text-muted-foreground">{{ description }}</p>
        </div>

        <div v-if="$slots.default" class="pt-1">
            <slot />
        </div>
    </div>
</template>
