<script setup lang="ts">
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-border/70 bg-background/95 px-4 backdrop-blur-md md:h-16 md:px-6"
    >
        <div class="flex min-w-0 items-center gap-3">
            <SidebarTrigger class="-ml-1 size-9 rounded-md" />
            <span v-if="breadcrumbs.length === 0" class="truncate text-sm font-semibold md:hidden">Meet-Go</span>
            <span v-else class="min-w-0 flex-1 truncate text-sm font-medium md:hidden">{{ breadcrumbs[breadcrumbs.length - 1].title }}</span>
            <template v-if="breadcrumbs.length > 0">
                <Breadcrumb class="hidden min-w-0 overflow-hidden md:block">
                    <BreadcrumbList class="flex-nowrap">
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem class="min-w-0">
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage class="truncate">{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href" class="truncate">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>
        <div class="ml-auto flex items-center gap-1">
            <ThemeToggle />
        </div>
    </header>
</template>
