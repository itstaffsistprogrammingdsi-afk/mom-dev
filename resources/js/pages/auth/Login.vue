<script setup lang="ts">
import GoogleIcon from '@/components/auth/GoogleIcon.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, LogIn, Mail } from 'lucide-vue-next';
import { onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        status?: string;
        canResetPassword: boolean;
        canRegister?: boolean;
        googleEnabled?: boolean;
    }>(),
    {
        googleEnabled: false,
    },
);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const { t } = useI18n();

const showPassword = ref(false);
const notice = ref('');
const helpDialog = ref<HTMLDialogElement | null>(null);
const year = new Date().getFullYear();
let noticeTimer: ReturnType<typeof setTimeout> | null = null;

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const showGoogleNotice = () => {
    notice.value = t('auth.googleUnavailable');

    if (noticeTimer) {
        clearTimeout(noticeTimer);
    }

    noticeTimer = setTimeout(() => {
        notice.value = '';
    }, 4000);
};

const openHelp = () => {
    helpDialog.value?.showModal();
};

onBeforeUnmount(() => {
    if (noticeTimer) {
        clearTimeout(noticeTimer);
    }
});
</script>

<template>
    <AuthBase :kicker="t('auth.kicker')" :title="t('auth.title')" :description="t('auth.description')">
        <Head :title="t('auth.submit')" />

        <div
            v-if="props.status"
            class="mb-4 rounded-xl border border-success/30 bg-success/10 px-3 py-2 text-center text-xs font-medium text-success-strong"
        >
            {{ props.status }}
        </div>

        <form class="login-form" @submit.prevent="submit">
            <div class="field-group">
                <label for="email">{{ t('auth.email') }}</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.email }">
                    <Mail class="field-icon" aria-hidden="true" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@perusahaan.com"
                        :aria-invalid="!!form.errors.email"
                        aria-describedby="email-error"
                    />
                </div>
                <small id="email-error" class="field-error" aria-live="polite">{{ form.errors.email }}</small>
            </div>

            <div class="field-group">
                <label for="password">{{ t('auth.password') }}</label>
                <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                    <LockKeyhole class="field-icon" aria-hidden="true" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        :aria-invalid="!!form.errors.password"
                        aria-describedby="password-error"
                    />
                    <button
                        type="button"
                        class="password-toggle"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" aria-hidden="true" />
                        <Eye v-else aria-hidden="true" />
                    </button>
                </div>
                <small id="password-error" class="field-error" aria-live="polite">{{ form.errors.password }}</small>
            </div>

            <div class="form-options">
                <label class="remember-control">
                    <input id="remember" v-model="form.remember" type="checkbox" />
                    <span class="custom-checkbox" aria-hidden="true">
                        <svg viewBox="0 0 16 16"><path d="m3 8 3.1 3.1L13 4.5" /></svg>
                    </span>
                    <span>{{ t('auth.remember') }}</span>
                </label>
                <Link v-if="props.canResetPassword" :href="route('password.request')" class="text-link"> {{ t('auth.forgot') }} </Link>
            </div>

            <button type="submit" class="primary-button" :class="{ 'is-loading': form.processing }" :disabled="form.processing">
                <span class="button-label">{{ t('auth.submit') }}</span>
                <LogIn class="button-arrow" aria-hidden="true" />
                <span class="button-spinner" aria-hidden="true"></span>
            </button>
        </form>

        <div class="separator"><span></span><small>{{ t('auth.or') }}</small><span></span></div>

        <a v-if="props.googleEnabled" :href="route('auth.google.redirect')" class="google-button">
            <GoogleIcon />
            <span>{{ t('auth.google') }}</span>
        </a>
        <button v-else type="button" class="google-button" @click="showGoogleNotice">
            <GoogleIcon />
            <span>{{ t('auth.google') }}</span>
        </button>

        <p v-if="props.canRegister" class="signup-note">
            {{ t('auth.noAccount') }}
            <Link :href="route('register')" class="text-link">{{ t('auth.register') }}</Link>
        </p>
        <p class="signup-note mt-2">
            {{ t('auth.help') }}
            <button type="button" class="text-link" @click="openHelp">{{ t('auth.contactAdmin') }}</button>
        </p>
        <p class="copyright">&copy; {{ year }} MoM. Workspace for better meetings.</p>

        <dialog ref="helpDialog" class="help-dialog">
            <div class="dialog-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path
                        d="M12 17.5v.01M9.1 9a3 3 0 1 1 5.2 2c-1 .9-2.3 1.4-2.3 3"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
                </svg>
            </div>
            <h3>{{ t('auth.helpTitle') }}</h3>
            <p>{{ t('auth.helpBody') }}</p>
            <button type="button" class="dialog-close" @click="helpDialog?.close()">Mengerti</button>
        </dialog>

        <div class="toast" :class="{ show: notice }" role="status" aria-live="polite">{{ notice }}</div>
    </AuthBase>
</template>
