<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="Atur ulang kata sandi" description="Buat kata sandi baru yang kuat untuk akun Anda.">
        <Head title="Atur ulang kata sandi" />

        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <Mail class="field-icon" aria-hidden="true" />
                    <input id="email" v-model="form.email" type="email" autocomplete="email" readonly />
                </div>
                <small class="field-error" aria-live="polite">{{ form.errors.email }}</small>
            </div>

            <div class="field-group">
                <label for="password">Kata sandi baru</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
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

            <div class="field-group">
                <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password_confirmation }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmation ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi kata sandi baru"
                        :aria-invalid="!!form.errors.password_confirmation"
                        aria-describedby="password-confirmation-error"
                    />
                    <button
                        type="button"
                        class="password-toggle"
                        :aria-label="showConfirmation ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'"
                        @click="showConfirmation = !showConfirmation"
                    >
                        <EyeOff v-if="showConfirmation" aria-hidden="true" />
                        <Eye v-else aria-hidden="true" />
                    </button>
                </div>
                <small id="password-confirmation-error" class="field-error" aria-live="polite">{{ form.errors.password_confirmation }}</small>
            </div>

            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span class="button-label">Simpan kata sandi baru</span>
                <LockKeyhole class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>
    </AuthLayout>
</template>
