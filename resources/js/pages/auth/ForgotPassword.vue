<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, Send } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout title="Lupa kata sandi" description="Masukkan alamat email Anda untuk menerima tautan pengaturan ulang kata sandi.">
        <Head title="Lupa kata sandi" />

        <div
            v-if="status"
            class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-xs font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
        >
            {{ status }}
        </div>

        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="email">Email</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.email }">
                    <Mail class="field-icon" aria-hidden="true" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nama@perusahaan.com"
                        :aria-invalid="!!form.errors.email"
                        aria-describedby="email-error"
                    />
                </div>
                <small id="email-error" class="field-error" aria-live="polite">{{ form.errors.email }}</small>
            </div>

            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span class="button-label">Kirim tautan reset</span>
                <Send class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>

        <p class="signup-note">
            Ingat kata sandi Anda?
            <Link :href="route('login')" class="text-link">Masuk</Link>
        </p>
    </AuthLayout>
</template>
