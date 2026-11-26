<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-1.5">
            <h1 class="text-[1.65rem] font-semibold leading-tight tracking-[-0.02em] text-foreground sm:text-title">{{ title }}</h1>
            <p v-if="description" class="max-w-[65ch] text-small leading-6 text-muted-foreground">{{ description }}</p>
        </div>

        <div class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>
