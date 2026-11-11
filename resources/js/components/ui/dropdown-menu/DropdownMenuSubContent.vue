<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuSubContent, useForwardPropsEmits, type DropdownMenuSubContentEmits, type DropdownMenuSubContentProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<DropdownMenuSubContentProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DropdownMenuSubContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DropdownMenuSubContent
        v-bind="forwarded"
        :class="
            cn(
                'z-50 min-w-40 overflow-hidden rounded-md border border-border/80 bg-popover p-1.5 text-popover-foreground shadow-popover data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:duration-150 data-[state=open]:duration-150 data-[side=bottom]:slide-in-from-top-1 data-[side=left]:slide-in-from-right-1 data-[side=right]:slide-in-from-left-1 data-[side=top]:slide-in-from-bottom-1',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuSubContent>
</template>
