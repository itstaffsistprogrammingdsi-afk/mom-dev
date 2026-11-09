<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
}>();

function prettyLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="rounded-md border px-3 py-1.5 text-sm transition"
                :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-muted'"
            >
                {{ prettyLabel(link.label) }}
            </Link>
            <span v-else class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground">
                {{ prettyLabel(link.label) }}
            </span>
        </template>
    </nav>
</template>
