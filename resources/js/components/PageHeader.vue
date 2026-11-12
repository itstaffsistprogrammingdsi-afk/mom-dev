<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-2">
            <h1 class="text-[1.625rem] font-semibold leading-tight tracking-tight text-foreground md:text-[1.75rem]">{{ title }}</h1>
            <p v-if="description" class="max-w-[65ch] text-sm leading-6 text-muted-foreground">{{ description }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>
