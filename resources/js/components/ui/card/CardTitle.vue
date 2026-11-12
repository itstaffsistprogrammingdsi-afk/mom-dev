<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        as?: string;
    }>(),
    {
        as: 'h3',
    },
);
</script>

<template>
    <component :is="props.as" :class="cn('text-base font-semibold leading-tight tracking-tight', props.class)">
        <slot />
    </component>
</template>
