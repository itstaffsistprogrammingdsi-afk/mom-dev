<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Tampilan',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tampilan" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Tampilan" description="Pilih tema terang, gelap, atau ikuti pengaturan sistem." />
                <AppearanceTabs />
                <p class="text-sm text-muted-foreground">
                    Mode sistem akan mengikuti preferensi perangkat Anda dan berubah otomatis saat tema perangkat berubah.
                </p>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
