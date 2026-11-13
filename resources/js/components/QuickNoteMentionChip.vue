<script setup lang="ts">
import type { QuickNoteMention } from '@/types';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        mention: QuickNoteMention;
        showStatus?: boolean;
    }>(),
    {
        showStatus: true,
    },
);

const name = computed(() => props.mention.user?.name ?? 'Pengguna');

const meta = computed(() => {
    const mention = props.mention;

    if (mention.responded_at) {
        if (mention.response_time_hours === null) {
            return 'sudah direspons';
        }

        return mention.response_time_hours < 1 ? 'direspons < 1 jam' : `direspons ${mention.response_time_hours} jam`;
    }

    if (mention.is_overdue) {
        return `lewat ${mention.overdue_hours ?? 0} jam`;
    }

    return `maks ${mention.response_hours} jam`;
});

const tone = computed(() => {
    if (props.mention.responded_at) {
        return props.mention.is_late ? 'border-warning/30 bg-warning/10 text-warning-strong' : 'border-success/25 bg-success/10 text-success-strong';
    }

    if (props.mention.is_overdue) {
        return 'border-destructive/30 bg-destructive/10 text-destructive-strong';
    }

    return 'border-border bg-muted/50 text-muted-foreground';
});
</script>

<template>
    <span :class="['inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs', tone]">
        <span class="truncate font-medium text-foreground">@{{ name }}</span>
        <span aria-hidden="true" class="text-border">·</span>
        <span class="shrink-0 tabular-nums">{{ meta }}</span>
        <span v-if="showStatus && mention.response_status_label" class="sr-only">Status {{ mention.response_status_label }}</span>
    </span>
</template>
