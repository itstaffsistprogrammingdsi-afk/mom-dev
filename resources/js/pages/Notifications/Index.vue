<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/labels';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { ref } from 'vue';

interface NotificationRow {
    id: string;
    data: { title?: string; message?: string; url?: string };
    read_at: string | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    notifications: Paginated<NotificationRow>;
    unreadCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifikasi', href: '/notifikasi' }];
const markingAll = ref(false);

function markAllRead() {
    markingAll.value = true;
    router.post(route('notifications.read-all'), {}, { preserveScroll: true, onFinish: () => (markingAll.value = false) });
}

function openNotification(notification: NotificationRow) {
    router.post(route('notifications.read', notification.id));
}
</script>

<template>
    <Head title="Notifikasi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Notifikasi" :description="`${props.unreadCount} notifikasi belum dibaca.`">
                <template #actions>
                    <Button v-if="props.unreadCount > 0" variant="outline" :loading="markingAll" @click="markAllRead"> Tandai semua terbaca </Button>
                </template>
            </PageHeader>

            <EmptyState
                v-if="props.notifications.data.length === 0"
                :icon="Bell"
                title="Belum ada notifikasi"
                description="Anda akan menerima pemberitahuan saat di-tag, diberi tugas, atau notulen difinalkan."
            />

            <Card v-else>
                <CardContent class="divide-y divide-border/60 p-0">
                    <button
                        v-for="notification in props.notifications.data"
                        :key="notification.id"
                        type="button"
                        class="flex w-full items-start gap-3 p-4 text-left transition-colors duration-fast ease-out-soft hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring/40"
                        :class="{ 'bg-accent/20': !notification.read_at }"
                        @click="openNotification(notification)"
                    >
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                            <Bell class="size-4" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1 space-y-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ notification.data.title ?? 'Pemberitahuan' }}</span>
                                <Badge v-if="!notification.read_at" variant="info">Baru</Badge>
                            </span>
                            <span class="block text-sm leading-relaxed text-muted-foreground">{{ notification.data.message }}</span>
                            <span class="block text-xs text-muted-foreground">{{ formatDateTime(notification.created_at) }}</span>
                        </span>
                        <span v-if="!notification.read_at" class="mt-2 size-2 shrink-0 rounded-full bg-primary" aria-label="Belum dibaca" />
                    </button>
                </CardContent>
            </Card>

            <Pagination :links="props.notifications.links" />
        </div>
    </AppLayout>
</template>
