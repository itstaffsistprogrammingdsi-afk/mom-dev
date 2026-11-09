<script setup lang="ts">
import { actionStatusClasses, actionStatusLabels, confidentialityClasses, confidentialityLabels, statusClasses, statusLabels } from '@/lib/labels';
import { computed } from 'vue';

const props = defineProps<{
    kind: 'meeting' | 'confidentiality' | 'action';
    value: string;
}>();

const classes = computed(() => {
    if (props.kind === 'confidentiality') return confidentialityClasses[props.value] ?? '';
    if (props.kind === 'action') return actionStatusClasses[props.value] ?? '';
    return statusClasses[props.value] ?? '';
});

const label = computed(() => {
    if (props.kind === 'confidentiality') return confidentialityLabels[props.value] ?? props.value;
    if (props.kind === 'action') return actionStatusLabels[props.value] ?? props.value;
    return statusLabels[props.value] ?? props.value;
});
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="classes">{{ label }}</span>
</template>
