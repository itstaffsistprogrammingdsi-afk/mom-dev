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
            class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/55 backdrop-blur-[2px] duration-dialog data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 motion-reduce:animate-none"
        >
            <DialogContent
                :class="
                    cn(
                        'relative z-50 my-8 grid w-[calc(100%-2rem)] max-w-lg gap-4 rounded-xl border border-border bg-card p-5 text-card-foreground shadow-popover duration-dialog ease-ui sm:p-6',
                        props.class,
                    )
                "
                v-bind="forwarded"
                @pointer-down-outside="
                    (event) => {
                        const originalEvent = event.detail.originalEvent;
                        const target = originalEvent.target as HTMLElement;
                        if (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) {
                            event.preventDefault();
                        }
                    }
                "
            >
                <slot />

                <DialogClose class="absolute right-3 top-3 rounded-md p-1 transition-colors duration-fast hover:bg-secondary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                    <X class="h-4 w-4" />
                    <span class="sr-only">Tutup</span>
                </DialogClose>
            </DialogContent>
        </DialogOverlay>
    </DialogPortal>
</template>
