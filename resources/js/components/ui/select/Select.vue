<script setup lang="ts">
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    modelValue?: string | number;
    defaultValue?: string | number;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (event: 'update:modelValue', value: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <select
        v-model="modelValue"
        :class="
            cn(
                'flex h-10 w-full rounded-md border border-input bg-card px-3 py-2 text-sm text-foreground shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-60 aria-[invalid=true]:border-destructive aria-[invalid=true]:ring-destructive',
                props.class,
            )
        "
    >
        <slot />
    </select>
</template>
