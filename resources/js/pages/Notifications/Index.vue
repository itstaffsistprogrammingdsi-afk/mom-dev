<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/labels';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

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

function markAllRead() {
    router.post(route('notifications.read-all'));
}

function openNotification(notification: NotificationRow) {
    router.post(route('notifications.read', notification.id));
}
</script>

<template>
    <Head title="Notifikasi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Notifikasi" :description="`${props.unreadCount} notifikasi belum dibaca.`">
                <template #actions>
                    <Button v-if="props.unreadCount > 0" variant="outline" @click="markAllRead">Tandai semua terbaca</Button>
                </template>
            </PageHeader>

            <EmptyState
                v-if="props.notifications.data.length === 0"
                title="Belum ada notifikasi"
                description="Anda akan menerima pemberitahuan saat di-tag, diberi tugas, atau notulen difinalkan."
            />

            <Card v-else>
                <CardContent class="divide-y p-0">
                    <button
                        v-for="notification in props.notifications.data"
                        :key="notification.id"
                        type="button"
                        class="flex w-full items-start justify-between gap-4 p-4 text-left transition hover:bg-muted/40"
                        :class="{ 'bg-muted/40': !notification.read_at }"
                        @click="openNotification(notification)"
                    >
                        <div class="space-y-1">
                            <p class="font-medium">{{ notification.data.title ?? 'Pemberitahuan' }}</p>
                            <p class="text-sm text-muted-foreground">{{ notification.data.message }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDateTime(notification.created_at) }}</p>
                        </div>
                        <span v-if="!notification.read_at" class="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                    </button>
                </CardContent>
            </Card>

            <Pagination :links="props.notifications.links" />
        </div>
    </AppLayout>
</template>
