<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MailCheck } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Verifikasi email" description="Verifikasi alamat email Anda melalui tautan yang telah kami kirimkan.">
        <Head title="Verifikasi email" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
        >
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat mendaftar.
        </div>

        <form class="login-form" @submit.prevent="submit">
            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span class="button-label">Kirim ulang email verifikasi</span>
                <MailCheck class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>

        <p class="signup-note">
            Ingin keluar dari akun ini?
            <Link :href="route('logout')" method="post" as="button" class="text-link">Keluar</Link>
        </p>
    </AuthLayout>
</template>
