<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const page = usePage<SharedData>();

function isActive(url: string): boolean {
    return page.url === url || (url !== '/dashboard' && page.url.startsWith(url));
}
</script>

<template>
    <nav :aria-label="label ?? 'Navigasi utama'">
        <SidebarGroup class="px-1.5 py-0.5">
            <SidebarGroupLabel>{{ label ?? 'Platform' }}</SidebarGroupLabel>
            <SidebarMenu class="gap-1">
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton as-child :is-active="isActive(item.url)" :tooltip="item.title">
                        <Link :href="item.url" :aria-current="isActive(item.url) ? 'page' : undefined">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <span
                                v-if="item.badge"
                                class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-semibold tabular-nums text-primary-foreground"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </nav>
</template>
