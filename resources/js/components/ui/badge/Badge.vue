<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';
import { badgeVariants, type BadgeVariants } from '.';

const props = withDefaults(
    defineProps<{
        variant?: BadgeVariants['variant'];
        class?: HTMLAttributes['class'];
    }>(),
    {
        variant: 'neutral',
    },
);
</script>

<template>
    <span :class="cn(badgeVariants({ variant }), props.class)">
        <slot />
    </span>
</template>
