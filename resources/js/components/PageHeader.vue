<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-2">
                <h1 class="text-2xl font-semibold leading-tight tracking-tight text-foreground sm:text-title">{{ title }}</h1>
                <p v-if="description" class="max-w-[65ch] text-small leading-6 text-muted-foreground">{{ description }}</p>
            </div>

            <div class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto sm:justify-end">
                <slot name="actions" />
            </div>
        </div>

        <div class="flex items-center gap-2" aria-hidden="true">
            <span class="h-px w-8 bg-primary/60" />
            <span class="h-px flex-1 bg-border" />
        </div>
    </div>
</template>
