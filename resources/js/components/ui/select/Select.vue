<script setup lang="ts">
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    modelValue?: string | number;
    defaultValue?: string | number;
    class?: HTMLAttributes['class'];
    wrapperClass?: HTMLAttributes['class'];
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
    <div :class="cn('relative', props.wrapperClass)">
        <select
            v-model="modelValue"
            v-bind="$attrs"
            :class="
                cn(
                    'flex h-10 w-full appearance-none rounded-md border border-input bg-card px-3 py-2 pr-9 text-sm text-foreground shadow-sm transition-[border-color,box-shadow] duration-fast ease-ui hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-muted disabled:opacity-60 aria-[invalid=true]:border-destructive aria-[invalid=true]:ring-destructive',
                    props.class,
                )
            "
        >
            <slot />
        </select>
        <ChevronDown class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
    </div>
</template>
