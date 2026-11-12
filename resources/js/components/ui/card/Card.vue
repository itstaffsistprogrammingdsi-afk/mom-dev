<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-lg border border-border bg-card text-card-foreground shadow-card', props.class)">
        <slot />
    </div>
</template>
