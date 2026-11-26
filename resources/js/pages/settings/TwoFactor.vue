<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    enabled: boolean;
    secret: string | null;
    otpauthUrl: string | null;
    recoveryCodes: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Autentikasi Dua Faktor', href: '/settings/two-factor' }];

const confirmForm = useForm({ code: '' });
const disableForm = useForm({ password: '' });

function confirm() {
    confirmForm.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset('code'),
    });
}

function disable() {
    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => disableForm.reset('password'),
    });
}
</script>

<template>
    <Head title="Autentikasi Dua Faktor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Autentikasi Dua Faktor"
                    description="Tambahkan lapisan keamanan kedua menggunakan aplikasi authenticator."
                />

                <div v-if="props.enabled" class="rounded-lg border border-success/30 bg-success/10 p-4 text-sm text-success-strong">
                    Autentikasi dua faktor aktif untuk akun ini.
                </div>

                <div v-else class="space-y-4 rounded-lg border border-border bg-card p-4">
                    <p class="text-sm text-muted-foreground">
                        Scan kode berikut dengan Google Authenticator, Microsoft Authenticator, atau aplikasi TOTP lain, lalu masukkan kode 6 digit.
                    </p>

                    <div class="space-y-1">
                        <p class="text-xs font-medium text-muted-foreground">Secret</p>
                        <code class="block break-all rounded-md bg-muted px-3 py-2 text-sm">{{ props.secret }}</code>
                    </div>

                    <a v-if="props.otpauthUrl" :href="props.otpauthUrl" class="break-all text-xs text-primary underline">
                        Buka tautan otpauth
                    </a>

                    <form class="space-y-2" @submit.prevent="confirm">
                        <Label for="two-factor-code">Kode 6 digit</Label>
                        <Input id="two-factor-code" v-model="confirmForm.code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
                        <InputError :message="confirmForm.errors.code" />
                        <Button type="submit" :loading="confirmForm.processing">Aktifkan 2FA</Button>
                    </form>
                </div>

                <div v-if="props.recoveryCodes.length" class="space-y-2 rounded-lg border border-warning/30 bg-warning/10 p-4">
                    <p class="text-sm font-medium text-warning-strong">Simpan recovery code ini di tempat aman.</p>
                    <p class="text-xs text-muted-foreground">Setiap kode hanya dapat digunakan sekali.</p>
                    <div class="grid gap-1 font-mono text-sm sm:grid-cols-2">
                        <span v-for="code in props.recoveryCodes" :key="code">{{ code }}</span>
                    </div>
                </div>

                <form v-if="props.enabled" class="space-y-3 rounded-lg border border-destructive/30 p-4" @submit.prevent="disable">
                    <p class="text-sm font-medium">Nonaktifkan 2FA</p>
                    <p class="text-xs text-muted-foreground">Masukkan kata sandi untuk mengonfirmasi.</p>
                    <Input v-model="disableForm.password" type="password" autocomplete="current-password" required />
                    <InputError :message="disableForm.errors.password" />
                    <Button type="submit" variant="destructive" :loading="disableForm.processing">Nonaktifkan</Button>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
