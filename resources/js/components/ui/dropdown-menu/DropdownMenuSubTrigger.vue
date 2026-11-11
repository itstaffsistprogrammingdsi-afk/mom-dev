<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-vue-next';
import { DropdownMenuSubTrigger, useForwardProps, type DropdownMenuSubTriggerProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<DropdownMenuSubTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuSubTrigger
        v-bind="forwardedProps"
        :class="
            cn(
                'flex cursor-pointer select-none items-center gap-2 rounded-sm px-2.5 py-2 text-sm text-secondary-foreground outline-none transition-colors duration-fast ease-out-soft focus:bg-accent focus:text-accent-foreground data-[state=open]:bg-accent data-[state=open]:text-accent-foreground',
                props.class,
            )
        "
    >
        <slot />
        <ChevronRight class="ml-auto size-4" />
    </DropdownMenuSubTrigger>
</template>
