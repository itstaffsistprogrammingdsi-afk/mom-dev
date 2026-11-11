<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { cn } from '@/lib/utils';
import { PanelLeft } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { useSidebar } from './utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const { toggleSidebar } = useSidebar();
</script>

<template>
    <Button
        data-sidebar="trigger"
        variant="ghost"
        size="icon"
        :class="cn('size-9 text-muted-foreground', props.class)"
        aria-label="Buka atau tutup sidebar"
        @click="toggleSidebar"
    >
        <PanelLeft />
        <span class="sr-only">Buka atau tutup sidebar</span>
    </Button>
</template>
