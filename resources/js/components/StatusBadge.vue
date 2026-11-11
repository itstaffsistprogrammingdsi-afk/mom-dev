<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { actionStatusLabels, confidentialityLabels, statusLabels } from '@/lib/labels';
import { computed } from 'vue';

const props = defineProps<{
    kind: 'meeting' | 'confidentiality' | 'action';
    value: string;
}>();

const meetingVariants: Record<string, BadgeVariants['variant']> = {
    draft: 'neutral',
    final: 'success',
    locked: 'outline',
};

const confidentialityVariants: Record<string, BadgeVariants['variant']> = {
    open: 'success',
    division: 'info',
    restricted: 'warning',
    secret: 'danger',
};

const actionVariants: Record<string, BadgeVariants['variant']> = {
    new: 'neutral',
    in_progress: 'info',
    done: 'success',
    cancelled: 'outline',
};

const variant = computed(() => {
    if (props.kind === 'confidentiality') return confidentialityVariants[props.value] ?? 'neutral';
    if (props.kind === 'action') return actionVariants[props.value] ?? 'neutral';
    return meetingVariants[props.value] ?? 'neutral';
});

const label = computed(() => {
    if (props.kind === 'confidentiality') return confidentialityLabels[props.value] ?? props.value;
    if (props.kind === 'action') return actionStatusLabels[props.value] ?? props.value;
    return statusLabels[props.value] ?? props.value;
});
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
