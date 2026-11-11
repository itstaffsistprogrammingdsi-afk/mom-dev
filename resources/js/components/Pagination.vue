<script setup lang="ts">
import { cn } from '@/lib/utils';
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
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Navigasi halaman">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                :aria-current="link.active ? 'page' : undefined"
                :class="
                    cn(
                        'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors duration-fast ease-out-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground shadow-soft-xs'
                            : 'border-border bg-surface text-muted-foreground hover:border-primary/20 hover:bg-accent hover:text-accent-foreground',
                    )
                "
            >
                {{ prettyLabel(link.label) }}
            </Link>
            <span
                v-else
                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border border-border bg-muted/40 px-3 text-sm text-subtle-foreground"
            >
                {{ prettyLabel(link.label) }}
            </span>
        </template>
    </nav>
</template>
