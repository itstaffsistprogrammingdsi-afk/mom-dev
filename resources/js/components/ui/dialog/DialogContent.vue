<script setup lang="ts">
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
    type DialogContentEmits,
    type DialogContentProps,
} from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed inset-0 z-50 bg-slate-950/55 backdrop-blur-[2px] duration-dialog data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 motion-reduce:animate-none"
        />
        <DialogContent
            v-bind="forwarded"
            :class="
                cn(
                    'fixed left-1/2 top-1/2 z-50 grid w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 rounded-xl border bg-card p-5 text-card-foreground shadow-popover duration-dialog ease-ui data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] motion-reduce:animate-none sm:p-6',
                    props.class,
                )
            "
        >
            <slot />

            <DialogClose
                class="absolute right-4 top-4 rounded-md p-1 opacity-70 ring-offset-background transition-[background-color,opacity] duration-fast hover:bg-accent hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none"
            >
                <X class="h-4 w-4" />
                <span class="sr-only">Tutup</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
