<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, FileText, LayoutGrid, ListChecks, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage<SharedData>();
const { t } = useI18n();

const canCreate = computed(() => page.props.auth.user?.role === 'admin' || page.props.auth.user?.role === 'notulis');
const unread = computed(() => page.props.notifications?.unread ?? 0);

const items = computed(() => [
    { key: 'dashboard', title: t('nav.dashboard'), url: '/dashboard', icon: LayoutGrid },
    { key: 'documents', title: t('nav.documents'), url: '/notulen', icon: FileText },
    { key: 'tasks', title: t('nav.tasks'), url: '/tugas', icon: ListChecks },
    { key: 'notifications', title: t('nav.notifications'), url: '/notifikasi', icon: Bell },
]);

function isActive(url: string): boolean {
    return page.url === url || (url !== '/dashboard' && page.url.startsWith(url));
}
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t border-border/80 bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md md:hidden"
        :aria-label="t('nav.workspace')"
    >
        <div class="mx-auto grid h-16 max-w-lg grid-cols-5 items-center px-2">
            <Link
                v-for="item in items.slice(0, 2)"
                :key="item.key"
                :href="item.url"
                class="relative flex flex-col items-center gap-0.5 rounded-lg px-1 py-2 text-[10px] font-medium transition-colors duration-fast"
                :class="isActive(item.url) ? 'text-primary' : 'text-muted-foreground'"
                :aria-current="isActive(item.url) ? 'page' : undefined"
            >
                <component :is="item.icon" class="size-5" aria-hidden="true" />
                <span class="max-w-full truncate">{{ item.title }}</span>
            </Link>

            <Link
                v-if="canCreate"
                :href="route('meetings.create')"
                class="-mt-7 flex flex-col items-center rounded-full"
                :aria-label="t('dashboard.newMeeting')"
            >
                <span class="flex size-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-popover">
                    <Plus class="size-5" aria-hidden="true" />
                </span>
            </Link>
            <span v-else aria-hidden="true"></span>

            <Link
                v-for="item in items.slice(2)"
                :key="item.key"
                :href="item.url"
                class="relative flex flex-col items-center gap-0.5 rounded-lg px-1 py-2 text-[10px] font-medium transition-colors duration-fast"
                :class="isActive(item.url) ? 'text-primary' : 'text-muted-foreground'"
                :aria-current="isActive(item.url) ? 'page' : undefined"
            >
                <component :is="item.icon" class="size-5" aria-hidden="true" />
                <span class="max-w-full truncate">{{ item.title }}</span>
                <span
                    v-if="item.key === 'notifications' && unread > 0"
                    class="absolute right-2 top-0.5 min-w-4 rounded-full bg-primary px-1 text-center text-[9px] leading-4 text-primary-foreground"
                >
                    {{ unread }}
                </span>
            </Link>
        </div>
    </nav>
</template>
