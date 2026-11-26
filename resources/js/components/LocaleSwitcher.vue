<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { setLocale, supportedLocales, type Locale } from '@/i18n';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Languages } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage<SharedData>();
const { t, locale } = useI18n();

const activeLocale = computed(() => locale.value as Locale);

function changeLocale(value: Locale) {
    setLocale(value);

    if (page.props.auth?.user) {
        router.put(
            route('interface-preferences.update'),
            { locale: value },
            { preserveScroll: true, preserveState: false, onSuccess: () => window.location.reload() },
        );

        return;
    }

    window.location.reload();
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" :title="t('language.change')" :aria-label="t('language.change')">
                <Languages class="size-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem
                v-for="option in supportedLocales"
                :key="option"
                class="flex items-center gap-2"
                @click="changeLocale(option)"
            >
                {{ t(`language.${option}`) }}
                <span v-if="activeLocale === option" class="ml-auto text-xs text-muted-foreground">{{ t('theme.active') }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
