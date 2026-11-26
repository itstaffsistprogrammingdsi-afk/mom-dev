<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ code: '' });

function submit() {
    form.post(route('two-factor.challenge.store'), {
        onFinish: () => form.reset('code'),
    });
}
</script>

<template>
    <Head title="Verifikasi dua langkah" />

    <AuthBase
        title="Verifikasi dua langkah"
        description="Masukkan kode 6 digit dari aplikasi authenticator atau recovery code."
        kicker="Keamanan akun"
    >
        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="two-factor-challenge-code">Kode autentikasi</label>
                <input
                    id="two-factor-challenge-code"
                    v-model="form.code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                    required
                    placeholder="123456"
                />
                <InputError :message="form.errors.code" />
            </div>

            <button type="submit" class="primary-button" :disabled="form.processing">
                <span class="button-label">{{ form.processing ? 'Memverifikasi…' : 'Verifikasi' }}</span>
            </button>
        </form>
    </AuthBase>
</template>
