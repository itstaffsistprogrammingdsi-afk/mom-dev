<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { documentTypeLabels } from '@/lib/labels';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    type: string;
    class?: HTMLAttributes['class'];
}>();

const variants: Record<string, BadgeVariants['variant']> = {
    meeting: 'info',
    proposal: 'outline',
    brochure: 'warning',
    report: 'success',
    other: 'neutral',
};
</script>

<template>
    <Badge :variant="variants[props.type] ?? 'neutral'" :class="props.class">
        {{ props.type ? (documentTypeLabels[props.type] ?? props.type) : 'Dokumen' }}
    </Badge>
</template>
