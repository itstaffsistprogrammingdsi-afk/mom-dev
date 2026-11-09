<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/labels';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

interface AuditRow {
    id: number;
    action: string;
    description: string | null;
    ip_address: string | null;
    created_at: string;
    user?: { id: number; name: string } | null;
    meeting?: { id: number; title: string } | null;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    logs: Paginated<AuditRow>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Audit Log', href: '/admin/audit' }];
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Audit Log" description="Jejak akses dan perubahan notulen untuk kebutuhan kepatuhan." />

            <EmptyState v-if="props.logs.data.length === 0" title="Belum ada aktivitas" />

            <div v-else class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Pengguna</th>
                            <th class="p-3">Aksi</th>
                            <th class="p-3">Notulen</th>
                            <th class="p-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="log in props.logs.data" :key="log.id">
                            <td class="whitespace-nowrap p-3">{{ formatDateTime(log.created_at) }}</td>
                            <td class="p-3">{{ log.user?.name ?? 'Sistem' }}</td>
                            <td class="p-3">
                                <p class="font-mono text-xs">{{ log.action }}</p>
                                <p class="text-xs text-muted-foreground">{{ log.description }}</p>
                            </td>
                            <td class="p-3">
                                <Link v-if="log.meeting" :href="route('meetings.show', log.meeting.id)" class="text-primary underline">
                                    {{ log.meeting.title }}
                                </Link>
                                <span v-else>—</span>
                            </td>
                            <td class="p-3 text-xs text-muted-foreground">{{ log.ip_address ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.logs.from ?? 0 }}–{{ props.logs.to ?? 0 }} dari {{ props.logs.total }} catatan
                </p>
                <Pagination :links="props.logs.links" />
            </div>
        </div>
    </AppLayout>
</template>
