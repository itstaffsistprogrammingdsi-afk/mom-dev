<script setup lang="ts">
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, Mail, UserRound, UserRoundPlus } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmation = ref(false);
const year = new Date().getFullYear();

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase kicker="Akun baru" title="Buat akun" description="Lengkapi data berikut untuk mulai menggunakan sistem.">
        <Head title="Daftar" />

        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="name">Nama lengkap</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.name }">
                    <UserRound class="field-icon" aria-hidden="true" />
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="name"
                        placeholder="Nama lengkap"
                        :aria-invalid="!!form.errors.name"
                        aria-describedby="name-error"
                    />
                </div>
                <small id="name-error" class="field-error" aria-live="polite">{{ form.errors.name }}</small>
            </div>

            <div class="field-group">
                <label for="email">Email</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.email }">
                    <Mail class="field-icon" aria-hidden="true" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        tabindex="2"
                        autocomplete="email"
                        placeholder="nama@perusahaan.com"
                        :aria-invalid="!!form.errors.email"
                        aria-describedby="email-error"
                    />
                </div>
                <small id="email-error" class="field-error" aria-live="polite">{{ form.errors.email }}</small>
            </div>

            <div class="field-group">
                <label for="password">Kata sandi</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        tabindex="3"
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
                <label for="password_confirmation">Konfirmasi kata sandi</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password_confirmation }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="showConfirmation ? 'text' : 'password'"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        placeholder="Ulangi kata sandi"
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

            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing" tabindex="5">
                <span class="button-label">Buat akun</span>
                <UserRoundPlus class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>

        <p class="signup-note">
            Sudah punya akun?
            <Link :href="route('login')" class="text-link">Masuk</Link>
        </p>
        <p class="copyright">&copy; {{ year }} MoM. Workspace for better meetings.</p>
    </AuthBase>
</template>
