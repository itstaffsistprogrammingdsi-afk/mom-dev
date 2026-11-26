<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { setLocale, supportedLocales, type Locale } from '@/i18n';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();
const page = usePage<SharedData>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('settings.appearance.title'),
        href: '/settings/appearance',
    },
];

function changeLocale(value: Locale) {
    setLocale(value);

    if (page.props.auth?.user) {
        router.put(route('interface-preferences.update'), { locale: value }, { preserveScroll: true, preserveState: false, onSuccess: () => window.location.reload() });

        return;
    }

    window.location.reload();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.appearance.title')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :title="t('settings.appearance.title')" :description="t('settings.appearance.description')" />
                <AppearanceTabs />
                <p class="text-sm text-muted-foreground">
                    {{ t('settings.appearance.systemHint') }}
                </p>

                <div class="space-y-2 border-t border-border/70 pt-6">
                    <HeadingSmall :title="t('settings.appearance.languageTitle')" :description="t('settings.appearance.languageHint')" />
                    <div class="flex flex-wrap gap-2" role="group" :aria-label="t('language.change')">
                        <Button
                            v-for="option in supportedLocales"
                            :key="option"
                            type="button"
                            :variant="locale === option ? 'default' : 'outline'"
                            size="sm"
                            @click="changeLocale(option)"
                        >
                            {{ t(`language.${option}`) }}
                        </Button>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
