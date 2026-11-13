<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import MeetingEditor from '@/components/MeetingEditor.vue';
import PageHeader from '@/components/PageHeader.vue';
import QuickNoteMentionChip from '@/components/QuickNoteMentionChip.vue';
import QuickNoteResponseDialog from '@/components/QuickNoteResponseDialog.vue';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/labels';
import type { BreadcrumbItem, QuickNote, QuickNoteMention, QuickNoteResponseStatus } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Clock3, MessageSquare, Pencil, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    note: QuickNote;
    can: { update: boolean; delete: boolean };
    currentUserId: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quick Note', href: '/quick-notes' },
    { title: props.note.title, href: `/quick-notes/${props.note.id}` },
];

const mentions = computed(() => props.note.mentions ?? []);

const contentWithResponseHours = computed(() => {
    const content = JSON.parse(JSON.stringify(props.note.content ?? { type: 'doc', content: [{ type: 'paragraph' }] })) as {
        type?: string;
        attrs?: Record<string, unknown>;
        content?: unknown[];
    };
    const hoursByUser = new Map<number, number>();

    for (const mention of mentions.value) {
        if (mention.user_id) {
            hoursByUser.set(mention.user_id, mention.response_hours);
        }
    }

    const walk = (node: { type?: string; attrs?: Record<string, unknown>; content?: unknown[] }) => {
        if (node.type === 'mention' && node.attrs?.type === 'person') {
            const hours = hoursByUser.get(Number(node.attrs.id));

            if (hours) {
                node.attrs = { ...node.attrs, responseHours: hours };
            }
        }

        for (const child of node.content ?? []) {
            if (child && typeof child === 'object') {
                walk(child as { type?: string; attrs?: Record<string, unknown>; content?: unknown[] });
            }
        }
    };

    walk(content);

    return content;
});
const respondedCount = computed(() => mentions.value.filter((mention) => mention.responded_at).length);
const pendingCount = computed(() => mentions.value.filter((mention) => !mention.responded_at).length);
const overdueCount = computed(() => mentions.value.filter((mention) => mention.is_overdue).length);
const responseProgress = computed(() => {
    if (mentions.value.length === 0) {
        return 0;
    }

    return Math.round((respondedCount.value / mentions.value.length) * 100);
});

const responseOpen = ref(false);
const activeMention = ref<QuickNoteMention | null>(null);
const deleteOpen = ref(false);

function openResponse(mention: QuickNoteMention) {
    activeMention.value = mention;
    responseOpen.value = true;
}

function isMyMention(mention: QuickNoteMention): boolean {
    return mention.user_id === props.currentUserId;
}

function statusTone(status: QuickNoteResponseStatus | null): string {
    switch (status) {
        case 'done':
            return 'border-success/25 bg-success/10 text-success-strong';
        case 'blocked':
        case 'need_help':
            return 'border-warning/30 bg-warning/10 text-warning-strong';
        case 'in_progress':
            return 'border-info/25 bg-info/10 text-info-strong';
        default:
            return 'border-border bg-muted/50 text-muted-foreground';
    }
}

function deleteNote() {
    router.delete(route('quick-notes.destroy', props.note.id), {
        onSuccess: () => {
            deleteOpen.value = false;
        },
    });
}
</script>

<template>
    <Head :title="props.note.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader :title="props.note.title">
                <template #actions>
                    <Button v-if="props.can.update" variant="outline" as-child>
                        <Link :href="route('quick-notes.edit', props.note.id)">
                            <Pencil class="size-4" />
                            Sunting
                        </Link>
                    </Button>
                    <Button v-if="props.can.delete" variant="ghost" class="text-destructive-strong" @click="deleteOpen = true">
                        <Trash2 class="size-4" />
                        Hapus
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                <span>Dibuat oleh {{ props.note.creator?.name ?? 'Pengguna' }}</span>
                <span class="inline-flex items-center gap-1.5">
                    <Clock3 class="size-4" aria-hidden="true" />
                    {{ formatDateTime(props.note.created_at) }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <Users class="size-4" aria-hidden="true" />
                    {{ mentions.length }} penerima
                </span>
                <span v-if="overdueCount" class="font-medium text-destructive-strong">{{ overdueCount }} lewat batas</span>
            </div>

            <div v-if="mentions.length" class="flex flex-wrap gap-1.5">
                <QuickNoteMentionChip v-for="mention in mentions" :key="mention.id" :mention="mention" />
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-6">
                    <section class="space-y-2">
                        <h2 class="text-sm font-semibold text-foreground">Isi catatan</h2>
                        <MeetingEditor
                            :model-value="contentWithResponseHours"
                            :users="[]"
                            :divisions="[]"
                            :topics="[]"
                            :editable="false"
                            :show-tag-picker="false"
                        />
                    </section>

                    <section class="rounded-lg border border-border bg-card" aria-labelledby="quick-note-recipients-heading">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border p-5">
                            <div>
                                <h2 id="quick-note-recipients-heading" class="flex items-center gap-2 text-sm font-semibold">
                                    <MessageSquare class="size-4 text-muted-foreground" />
                                    Penerima dan respons
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">Batas respons tampil di samping setiap tag.</p>
                            </div>
                            <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                {{ respondedCount }}/{{ mentions.length }} merespons
                            </span>
                        </div>

                        <div v-if="mentions.length === 0" class="p-5 text-sm text-muted-foreground">
                            Catatan ini belum memiliki penerima yang di-tag.
                        </div>

                        <ul v-else class="divide-y divide-border">
                            <li v-for="mention in mentions" :key="mention.id" class="space-y-3 p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span
                                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-[11px] font-semibold text-secondary-foreground"
                                            aria-hidden="true"
                                        >
                                            {{ getInitials(mention.user?.name ?? 'Pengguna') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{{ mention.user?.name ?? 'Pengguna' }}</p>
                                            <div class="mt-1.5">
                                                <QuickNoteMentionChip :mention="mention" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        <span
                                            v-if="mention.response_status_label"
                                            class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium"
                                            :class="statusTone(mention.response_status)"
                                        >
                                            <CheckCircle2 v-if="mention.response_status === 'done'" class="size-3.5" aria-hidden="true" />
                                            {{ mention.response_status_label }}
                                        </span>
                                        <Button
                                            v-if="isMyMention(mention)"
                                            size="sm"
                                            :variant="mention.is_overdue ? 'destructive' : 'default'"
                                            @click="openResponse(mention)"
                                        >
                                            {{ mention.responded_at ? 'Perbarui respons' : 'Beri respons' }}
                                        </Button>
                                    </div>
                                </div>

                                <div class="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
                                    <p>
                                        Batas respons:
                                        <span :class="mention.is_overdue ? 'font-medium text-destructive-strong' : 'text-foreground'">
                                            {{ mention.due_at ? formatDateTime(mention.due_at) : `${mention.response_hours} jam` }}
                                        </span>
                                    </p>
                                    <p v-if="mention.responded_at">
                                        Direspons:
                                        <span class="text-foreground">{{ formatDateTime(mention.responded_at) }}</span>
                                        <span v-if="mention.response_time_hours !== null">
                                            ({{ mention.response_time_hours < 1 ? '< 1 jam' : `${mention.response_time_hours} jam` }})
                                        </span>
                                    </p>
                                    <p v-else>
                                        Status:
                                        <span :class="mention.is_overdue ? 'font-medium text-destructive-strong' : 'text-foreground'">
                                            {{ mention.is_overdue ? 'Lewat batas' : 'Menunggu respons' }}
                                        </span>
                                    </p>
                                </div>

                                <p v-if="mention.response_body" class="rounded-md bg-muted/40 p-3 text-sm leading-6 text-foreground">
                                    {{ mention.response_body }}
                                </p>
                            </li>
                        </ul>
                    </section>
                </div>

                <aside class="min-w-0 space-y-4 lg:sticky lg:top-20">
                    <section class="rounded-lg border border-border bg-card p-5">
                        <h2 class="text-sm font-semibold">Ringkasan respons</h2>
                        <div class="mt-3 flex items-center gap-3">
                            <div
                                class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                :aria-valuenow="responseProgress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Progres respons Quick Note"
                            >
                                <div
                                    class="h-full origin-left rounded-full bg-success transition-transform duration-popover ease-ui motion-reduce:transition-none"
                                    :style="{ transform: `scaleX(${responseProgress / 100})` }"
                                ></div>
                            </div>
                            <span class="text-xs tabular-nums text-muted-foreground">{{ responseProgress }}%</span>
                        </div>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-md bg-muted/40 p-2.5">
                                <dt class="text-[11px] text-muted-foreground">Merrespons</dt>
                                <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ respondedCount }}</dd>
                            </div>
                            <div class="rounded-md bg-muted/40 p-2.5">
                                <dt class="text-[11px] text-muted-foreground">Menunggu</dt>
                                <dd class="mt-0.5 text-lg font-semibold tabular-nums">{{ pendingCount }}</dd>
                            </div>
                            <div class="rounded-md bg-muted/40 p-2.5">
                                <dt class="text-[11px] text-muted-foreground">Lewat</dt>
                                <dd class="mt-0.5 text-lg font-semibold tabular-nums" :class="overdueCount ? 'text-destructive-strong' : ''">
                                    {{ overdueCount }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-border bg-card p-5">
                        <h2 class="text-sm font-semibold">Informasi</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div>
                                <dt class="text-xs text-muted-foreground">Dibuat</dt>
                                <dd class="mt-0.5">{{ formatDateTime(props.note.created_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">Diperbarui</dt>
                                <dd class="mt-0.5">{{ formatDateTime(props.note.updated_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-muted-foreground">Visibilitas</dt>
                                <dd class="mt-0.5 text-muted-foreground">Pembuat, penerima tag, dan admin.</dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
        </div>

        <QuickNoteResponseDialog v-model:open="responseOpen" :note-id="props.note.id" :mention="activeMention" />

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Hapus Quick Note?"
            description="Catatan beserta data responsnya akan dihapus permanen."
            confirm-label="Hapus catatan"
            @confirm="deleteNote"
        />
    </AppLayout>
</template>
