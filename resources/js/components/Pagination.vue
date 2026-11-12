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
        .replace(/&amp;/g, '&')
        .replace(/Previous/g, 'Sebelumnya')
        .replace(/Next/g, 'Berikutnya');
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Paginasi">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                :aria-current="link.active ? 'page' : undefined"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-md border px-3 py-1.5 text-sm transition-[background-color,border-color,color] duration-fast ease-ui focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                        : 'border-border bg-card text-foreground hover:bg-muted'
                "
            >
                {{ prettyLabel(link.label) }}
            </Link>
            <span
                v-else
                aria-disabled="true"
                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-md border border-border bg-card px-3 py-1.5 text-sm text-muted-foreground opacity-60"
            >
                {{ prettyLabel(link.label) }}
            </span>
        </template>
    </nav>
</template>
