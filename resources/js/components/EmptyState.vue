<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-12 text-center">
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="max-w-md text-sm text-muted-foreground">{{ description }}</p>
        <slot />
    </div>
</template>
