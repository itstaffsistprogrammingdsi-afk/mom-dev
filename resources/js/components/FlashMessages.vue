<script setup lang="ts">
import ToastViewport from '@/components/ui/toast/ToastViewport.vue';
import { useToast } from '@/composables/useToast';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const page = usePage<SharedData>();
const toast = useToast();

const success = computed(() => page.props.flash?.success);
const error = computed(() => page.props.flash?.error);

watch(
    [success, error],
    ([successMessage, errorMessage]) => {
        if (successMessage) {
            toast.success('Berhasil', successMessage);
        }

        if (errorMessage) {
            toast.error('Terjadi kesalahan', errorMessage);
        }
    },
    { immediate: true },
);
</script>

<template>
    <ToastViewport />
</template>
