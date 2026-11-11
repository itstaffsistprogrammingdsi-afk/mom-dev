<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    interactive?: boolean;
}>();
</script>

<template>
    <div
        :class="
            cn(
                'rounded-lg border border-border/80 bg-card text-card-foreground shadow-soft-sm',
                props.interactive &&
                    'transition-[border-color,box-shadow] duration-base ease-out-soft hover:border-primary/20 hover:shadow-soft-md',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
