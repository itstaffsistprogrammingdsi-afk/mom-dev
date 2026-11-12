<script setup lang="ts">
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, X, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage<SharedData>();

const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);
const dismissed = ref(false);

watch([success, error], () => {
    dismissed.value = false;
});
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-3 z-50 flex flex-col items-center gap-2 px-4 sm:top-5">
        <Transition
            enter-active-class="transition-[opacity,transform] duration-popover ease-ui motion-reduce:transition-none"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition-[opacity,transform] duration-fast ease-ui motion-reduce:transition-none"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="success && !dismissed"
                role="status"
                aria-live="polite"
                class="pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-lg border border-success/30 bg-card p-3.5 text-sm text-card-foreground shadow-popover"
            >
                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-success" />
                <p class="flex-1">{{ success }}</p>
                <button
                    type="button"
                    class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Tutup pesan sukses"
                    @click="dismissed = true"
                >
                    <X class="size-4" />
                </button>
            </div>
        </Transition>
        <Transition
            enter-active-class="transition-[opacity,transform] duration-popover ease-ui motion-reduce:transition-none"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition-[opacity,transform] duration-fast ease-ui motion-reduce:transition-none"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-if="error && !dismissed"
                role="alert"
                class="pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-lg border border-destructive/30 bg-card p-3.5 text-sm text-card-foreground shadow-popover"
            >
                <XCircle class="mt-0.5 size-4 shrink-0 text-destructive" />
                <p class="flex-1">{{ error }}</p>
                <button
                    type="button"
                    class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Tutup pesan error"
                    @click="dismissed = true"
                >
                    <X class="size-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
