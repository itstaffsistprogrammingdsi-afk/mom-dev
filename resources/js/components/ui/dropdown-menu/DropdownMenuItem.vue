<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuItem, useForwardProps, type DropdownMenuItemProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class']; inset?: boolean }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuItem
        v-bind="forwardedProps"
        :class="
            cn(
                'relative flex cursor-default select-none items-center gap-2 rounded-md px-2.5 py-2 text-sm outline-none transition-colors duration-fast ease-ui hover:bg-accent focus:bg-accent focus:text-accent-foreground focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&>svg]:size-4 [&>svg]:shrink-0',
                inset && 'pl-8',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuItem>
</template>
