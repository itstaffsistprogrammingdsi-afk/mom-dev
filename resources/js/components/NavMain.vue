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
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel class="mb-1 px-3 text-[11px] font-semibold text-sidebar-foreground/80">{{ label ?? 'Platform' }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :tooltip="item.title"
                    :is-active="isActive(item.url)"
                    class="h-10 rounded-md px-3 font-medium transition-[background-color,color,box-shadow] duration-fast ease-ui data-[active=true]:shadow-sm max-sm:h-11"
                >
                    <Link :href="item.url" :aria-current="isActive(item.url) ? 'page' : undefined">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="item.badge"
                            class="ml-auto rounded-full bg-sidebar-primary px-2 py-0.5 text-[11px] font-medium text-sidebar-primary-foreground"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
