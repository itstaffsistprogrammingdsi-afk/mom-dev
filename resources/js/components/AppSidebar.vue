<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Building2,
    FileStack,
    FileText,
    Hash,
    Inbox,
    LayoutGrid,
    ListChecks,
    ScrollText,
    ShieldCheck,
    StickyNote,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const { t } = useI18n();

const mainNavItems = computed<NavItem[]>(() => [
    { title: t('nav.dashboard'), url: '/dashboard', icon: LayoutGrid },
    { title: t('nav.documents'), url: '/notulen', icon: FileText },
    { title: t('nav.quickNotes'), url: '/quick-notes', icon: StickyNote },
    { title: t('nav.related'), url: '/terkait-saya', icon: Inbox },
    { title: t('nav.tasks'), url: '/tugas', icon: ListChecks },
    { title: t('nav.topics'), url: '/topik', icon: Hash },
    {
        title: t('nav.notifications'),
        url: '/notifikasi',
        icon: Bell,
        badge: page.props.notifications?.unread || undefined,
    },
]);

const adminNavItems = computed<NavItem[]>(() => [
    { title: t('nav.divisions'), url: '/admin/divisi', icon: Building2 },
    { title: t('nav.people'), url: '/admin/pengguna', icon: Users },
    { title: t('nav.roles'), url: '/admin/peran', icon: ShieldCheck },
    { title: t('nav.manageTopics'), url: '/admin/topik', icon: Hash },
    { title: t('nav.templates'), url: '/admin/template', icon: FileStack },
    { title: t('nav.audit'), url: '/admin/audit', icon: ScrollText },
]);

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border/70 px-3 py-3 pr-12 md:pr-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="rounded-lg px-2 hover:bg-sidebar-accent">
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent role="navigation" :aria-label="t('nav.workspace')" class="gap-4 px-1 py-4">
            <NavMain :items="mainNavItems" :label="t('nav.workspace')" />
            <NavMain v-if="isAdmin" :items="adminNavItems" :label="t('nav.administration')" />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/70 p-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
