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
    <div class="pointer-events-none fixed inset-x-0 top-3 z-50 flex flex-col items-center gap-2 px-4">
        <div
            v-if="success && !dismissed"
            class="pointer-events-auto flex w-full max-w-lg items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900 shadow-sm dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" />
            <p class="flex-1">{{ success }}</p>
            <button type="button" aria-label="Tutup" @click="dismissed = true"><X class="size-4" /></button>
        </div>
        <div
            v-if="error && !dismissed"
            class="pointer-events-auto flex w-full max-w-lg items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900 shadow-sm dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100"
        >
            <XCircle class="mt-0.5 size-4 shrink-0" />
            <p class="flex-1">{{ error }}</p>
            <button type="button" aria-label="Tutup" @click="dismissed = true"><X class="size-4" /></button>
        </div>
    </div>
</template>
