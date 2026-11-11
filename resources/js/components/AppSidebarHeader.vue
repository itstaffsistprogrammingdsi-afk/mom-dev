<script setup lang="ts">
import KeyboardShortcutsDialog from '@/components/KeyboardShortcutsDialog.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Keyboard } from 'lucide-vue-next';
import { computed, ref } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<SharedData>();
const unread = computed(() => page.props.notifications?.unread ?? 0);
const shortcutsOpen = ref(false);
</script>

<template>
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-border/70 bg-background px-4 lg:px-6">
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />

            <span class="hidden h-5 w-px bg-border sm:block" aria-hidden="true" />

            <Breadcrumb v-if="breadcrumbs.length > 0" class="hidden min-w-0 sm:block">
                <BreadcrumbList class="flex-nowrap">
                    <template v-for="(item, index) in breadcrumbs" :key="index">
                        <BreadcrumbItem class="min-w-0">
                            <template v-if="index === breadcrumbs.length - 1">
                                <BreadcrumbPage class="max-w-[42vw] truncate lg:max-w-md">{{ item.title }}</BreadcrumbPage>
                            </template>
                            <template v-else>
                                <BreadcrumbLink :href="item.href" class="max-w-40 truncate">{{ item.title }}</BreadcrumbLink>
                            </template>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                    </template>
                </BreadcrumbList>
            </Breadcrumb>
        </div>

        <div class="ml-auto flex items-center gap-1">
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors duration-fast ease-out-soft hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                aria-label="Pintasan keyboard"
                @click="shortcutsOpen = true"
            >
                <Keyboard class="size-4" aria-hidden="true" />
            </button>

            <Link
                :href="route('notifications.index')"
                class="relative inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors duration-fast ease-out-soft hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"
                :aria-label="unread > 0 ? `Notifikasi, ${unread} belum dibaca` : 'Notifikasi'"
            >
                <Bell class="size-4" aria-hidden="true" />
                <span v-if="unread > 0" class="absolute right-1.5 top-1.5 size-1.5 rounded-full bg-primary ring-2 ring-background" />
            </Link>

            <ThemeToggle />
        </div>

        <KeyboardShortcutsDialog v-model:open="shortcutsOpen" />
    </header>
</template>
