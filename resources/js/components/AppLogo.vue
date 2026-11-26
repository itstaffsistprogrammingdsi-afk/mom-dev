<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useI18n } from 'vue-i18n';

interface Props {
    class?: string;
}

defineProps<Props>();

const { t } = useI18n();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none tracking-tight">MoM</span>
        <span class="truncate text-[11px] text-subtle-foreground">{{ t('app.tagline') }}</span>
    </div>
</template>
