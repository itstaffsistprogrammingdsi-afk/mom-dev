<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { documentTypeLabels, formatDate, formatDateTime, meetingTypeLabels } from '@/lib/labels';
import { type ActionItem, type BreadcrumbItem, type Meeting, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CalendarDays, Hash, ListChecks, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        meetings_this_month: number;
        open_tasks: number;
        overdue_tasks: number;
        active_topics: number;
    };
    upcoming: Meeting[];
    recent: Meeting[];
    myTasks: ActionItem[];
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const today = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
}).format(new Date());
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader
                :title="`Selamat datang, ${user.name.split(' ')[0]}`"
                :description="`${today} · ${user.division?.name ?? 'Belum ada divisi'}`"
            >
                <template #actions>
                    <Button v-if="user.role === 'admin' || user.role === 'notulis'" as-child>
                        <Link :href="route('meetings.create')">
                            <Plus class="size-4" />
                            Buat dokumen
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Rapat bulan ini" :value="props.stats.meetings_this_month" :icon="CalendarDays" />
                <StatCard
                    label="Action item terbuka"
                    :value="props.stats.open_tasks"
                    :icon="ListChecks"
                    hint="Seluruh dokumen yang dapat Anda akses"
                />
                <StatCard
                    label="Terlambat"
                    :value="props.stats.overdue_tasks"
                    :icon="AlertTriangle"
                    :tone="props.stats.overdue_tasks > 0 ? 'danger' : 'success'"
                />
                <StatCard label="Topik aktif" :value="props.stats.active_topics" :icon="Hash" />
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader class="flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-base">Rapat mendatang</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('meetings.index')">Lihat semua</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <EmptyState v-if="props.upcoming.length === 0" title="Belum ada rapat mendatang" />
                        <Link
                            v-for="meeting in props.upcoming"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="flex items-start justify-between gap-3 rounded-lg border p-3 transition hover:bg-muted/50"
                        >
                            <div class="space-y-1">
                                <p class="font-medium leading-tight">{{ meeting.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDateTime(meeting.scheduled_at) }}
                                    <span v-if="meeting.division">· {{ meeting.division.name }}</span>
                                </p>
                            </div>
                            <StatusBadge kind="meeting" :value="meeting.status" />
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex-row items-center justify-between space-y-0">
                        <CardTitle class="text-base">Tugas saya</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('action-items.index')">Semua tugas</Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <EmptyState v-if="props.myTasks.length === 0" title="Tidak ada tugas terbuka" />
                        <Link
                            v-for="task in props.myTasks"
                            :key="task.id"
                            :href="route('meetings.show', task.meeting?.id ?? 0)"
                            class="flex items-start justify-between gap-3 rounded-lg border p-3 transition hover:bg-muted/50"
                        >
                            <div class="space-y-1">
                                <p class="font-medium leading-tight">{{ task.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ task.meeting?.title }} · tenggat {{ formatDate(task.due_date) }}</p>
                            </div>
                            <span v-if="task.is_overdue" class="text-xs font-medium text-rose-600">Terlambat</span>
                            <StatusBadge v-else kind="action" :value="task.status" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-base">Dokumen terbaru</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="route('meetings.index')">Buka daftar</Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <EmptyState v-if="props.recent.length === 0" title="Belum ada dokumen" description="Mulai dengan membuat dokumen pertama." />
                    <div class="divide-y">
                        <Link
                            v-for="meeting in props.recent"
                            :key="meeting.id"
                            :href="route('meetings.show', meeting.id)"
                            class="flex flex-wrap items-center justify-between gap-2 py-3 transition hover:text-primary"
                        >
                            <div>
                                <p class="font-medium">{{ meeting.title }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ documentTypeLabels[meeting.document_type] ?? meetingTypeLabels[meeting.meeting_type] }} ·
                                    {{ formatDate(meeting.scheduled_at) }} ·
                                    {{ meeting.division?.name ?? 'Tanpa divisi' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <StatusBadge kind="confidentiality" :value="meeting.confidentiality" />
                                <StatusBadge kind="meeting" :value="meeting.status" />
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
