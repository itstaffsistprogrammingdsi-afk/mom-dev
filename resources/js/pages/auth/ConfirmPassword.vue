<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    password: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout title="Konfirmasi kata sandi" description="Area ini dilindungi. Konfirmasi kata sandi Anda untuk melanjutkan.">
        <Head title="Konfirmasi kata sandi" />

        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="password">Kata sandi</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        :aria-invalid="!!form.errors.password"
                        aria-describedby="password-error"
                    />
                    <button
                        type="button"
                        class="password-toggle"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" aria-hidden="true" />
                        <Eye v-else aria-hidden="true" />
                    </button>
                </div>
                <small id="password-error" class="field-error" aria-live="polite">{{ form.errors.password }}</small>
            </div>

            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span class="button-label">Konfirmasi</span>
                <LockKeyhole class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>
    </AuthLayout>
</template>
