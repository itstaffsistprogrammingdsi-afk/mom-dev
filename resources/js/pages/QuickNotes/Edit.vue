<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import QuickNoteComposer from '@/components/QuickNoteComposer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Division, QuickNote } from '@/types';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    note: QuickNote;
    users: { id: number; name: string; email?: string; division_id?: number | null }[];
    divisions: Division[];
    topics: { id: number; name: string }[];
    responseOptions: { value: number; label: string }[];
    emptyContent: Record<string, unknown>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Quick Note', href: '/quick-notes' },
    { title: props.note.title, href: `/quick-notes/${props.note.id}` },
    { title: 'Sunting', href: `/quick-notes/${props.note.id}/sunting` },
];
</script>

<template>
    <Head :title="`Sunting ${props.note.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
            <PageHeader
                title="Sunting Quick Note"
                description="Perbarui isi catatan, penerima, atau batas respons. Respons yang sudah masuk tetap tersimpan."
            />

            <QuickNoteComposer
                :note="props.note"
                :users="props.users"
                :divisions="props.divisions"
                :topics="props.topics"
                :response-options="props.responseOptions"
                :empty-content="props.emptyContent"
            />
        </div>
    </AppLayout>
</template>
