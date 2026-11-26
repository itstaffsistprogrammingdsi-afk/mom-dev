<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});

const { appearance, updateAppearance } = useAppearance();
const { t } = useI18n();

const tabs = computed(() => [
    { value: 'light' as const, Icon: Sun, label: t('theme.light') },
    { value: 'dark' as const, Icon: Moon, label: t('theme.dark') },
    { value: 'system' as const, Icon: Monitor, label: t('theme.system') },
]);
</script>

<template>
    <div :class="['inline-flex gap-1 rounded-lg border border-border/70 bg-muted/50 p-1', props.class]">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            class="flex min-h-10 items-center rounded-md px-3.5 py-1.5 text-sm transition-colors duration-fast"
            :class="
                appearance === value
                    ? 'bg-surface text-foreground shadow-soft-xs'
                    : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground'
            "
            @click="updateAppearance(value)"
        >
            <component :is="Icon" class="-ml-1 size-4" aria-hidden="true" />
            <span class="ml-1.5">{{ label }}</span>
        </button>
    </div>
</template>
