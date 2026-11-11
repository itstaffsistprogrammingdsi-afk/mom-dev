<script setup lang="ts">
import Toast from './Toast.vue';
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:right-4 sm:items-end"
            aria-live="polite"
            aria-atomic="false"
        >
            <TransitionGroup
                enter-active-class="transition duration-base ease-out-soft"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-fast ease-out-soft"
                leave-from-class="opacity-100"
                leave-to-class="translate-y-1 opacity-0"
                move-class="transition duration-fast ease-out-soft"
            >
                <Toast v-for="toast in toasts" :key="toast.id" :toast="toast" @close="dismiss(toast.id)" />
            </TransitionGroup>
        </div>
    </Teleport>
</template>
