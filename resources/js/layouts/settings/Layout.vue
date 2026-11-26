<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const sidebarNavItems = computed<NavItem[]>(() => [
    { title: t('settings.nav.profile'), url: '/settings/profile' },
    { title: t('settings.nav.password'), url: '/settings/password' },
    { title: t('settings.nav.twoFactor'), url: '/settings/two-factor' },
    { title: t('settings.nav.notifications'), url: '/settings/notifikasi' },
    { title: t('settings.nav.appearance'), url: '/settings/appearance' },
]);

const currentPath = window.location.pathname;
</script>

<template>
    <div class="flex flex-col gap-section p-page lg:p-page-lg">
        <PageHeader :title="t('nav.settings')" :description="t('settings.description')" />

        <div class="grid gap-6 lg:grid-cols-[200px_minmax(0,1fr)] lg:items-start">
            <aside class="lg:sticky lg:top-20">
                <nav class="flex flex-col gap-1" :aria-label="t('nav.settings')">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="item.url"
                        :href="item.url"
                        :aria-current="currentPath === item.url ? 'page' : undefined"
                        :class="
                            cn(
                                'rounded-md px-3 py-2 text-sm font-medium transition-colors duration-fast ease-out-soft',
                                currentPath === item.url
                                    ? 'bg-surface text-foreground shadow-soft-xs ring-1 ring-border/70'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <Separator class="lg:hidden" />

            <div class="min-w-0 max-w-2xl">
                <section class="space-y-10">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
