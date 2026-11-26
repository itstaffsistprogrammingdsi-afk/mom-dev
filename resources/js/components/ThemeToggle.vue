<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { appearance, updateAppearance } = useAppearance();
const { t } = useI18n();

const options = computed(() => [
    { value: 'light' as const, label: t('theme.light'), Icon: Sun },
    { value: 'dark' as const, label: t('theme.dark'), Icon: Moon },
    { value: 'system' as const, label: t('theme.system'), Icon: Monitor },
]);

const current = computed(() => options.value.find((option) => option.value === appearance.value) ?? options.value[2]);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" :title="`${t('theme.change')}: ${current.label}`" :aria-label="t('theme.change')">
                <component :is="current.Icon" class="size-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem v-for="option in options" :key="option.value" class="flex items-center gap-2" @click="updateAppearance(option.value)">
                <component :is="option.Icon" class="size-4" />
                {{ option.label }}
                <span v-if="appearance === option.value" class="ml-auto text-xs text-muted-foreground">{{ t('theme.active') }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
