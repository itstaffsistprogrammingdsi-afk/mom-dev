<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="rounded-lg border border-border/80 bg-card shadow-soft-sm" aria-busy="true" aria-label="Memuat editor">
        <div class="flex flex-wrap items-center gap-1 border-b border-border/70 p-2">
            <Skeleton v-for="index in 5" :key="index" class="size-8 rounded-md" />
        </div>

        <div class="mx-auto max-w-reading space-y-3 p-6 sm:p-10">
            <Skeleton class="h-6 w-1/3" />
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-4 w-5/6" />
            <Skeleton class="h-4 w-4/5" />
            <Skeleton class="mt-6 h-6 w-1/4" />
            <Skeleton class="h-4 w-full" />
            <Skeleton class="h-4 w-3/4" />
        </div>
    </div>
</template>
