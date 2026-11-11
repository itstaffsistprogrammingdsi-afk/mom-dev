<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Building2, FileStack, FileText, Hash, Inbox, LayoutGrid, ListChecks, ScrollText, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();

const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
    { title: 'Dokumen', url: '/notulen', icon: FileText },
    { title: 'Terkait Saya', url: '/terkait-saya', icon: Inbox },
    { title: 'Action Item', url: '/tugas', icon: ListChecks },
    { title: 'Topik', url: '/topik', icon: Hash },
    {
        title: 'Notifikasi',
        url: '/notifikasi',
        icon: Bell,
        badge: page.props.notifications?.unread || undefined,
    },
]);

const adminNavItems = computed<NavItem[]>(() => [
    { title: 'Divisi', url: '/admin/divisi', icon: Building2 },
    { title: 'Pengguna', url: '/admin/pengguna', icon: Users },
    { title: 'Peran & Akses', url: '/admin/peran', icon: ShieldCheck },
    { title: 'Topik', url: '/admin/topik', icon: Hash },
    { title: 'Template', url: '/admin/template', icon: FileStack },
    { title: 'Audit Log', url: '/admin/audit', icon: ScrollText },
]);

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="p-3 pb-1">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" aria-label="Ke dashboard MoM">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="py-1">
            <NavMain :items="mainNavItems" label="Ruang Kerja" />
            <SidebarSeparator v-if="isAdmin" class="my-2" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Administrasi" />
        </SidebarContent>

        <SidebarFooter class="p-2">
            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
    <slot />
</template>
