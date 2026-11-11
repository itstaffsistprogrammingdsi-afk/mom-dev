<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { attendanceLabels } from '@/lib/labels';

const props = defineProps<{
    value: string;
}>();

const variants: Record<string, BadgeVariants['variant']> = {
    invited: 'neutral',
    present: 'success',
    absent: 'danger',
    excused: 'warning',
};
</script>

<template>
    <Badge :variant="variants[props.value] ?? 'neutral'">
        {{ attendanceLabels[props.value] ?? props.value }}
    </Badge>
</template>
