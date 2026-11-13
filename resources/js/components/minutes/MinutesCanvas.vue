<script setup lang="ts">
import MeetingEditor from '@/components/MeetingEditor.vue';
import MinutesSheet from '@/components/minutes/MinutesSheet.vue';
import { formatDateTime } from '@/lib/labels';
import type { Division } from '@/types';
import type { JSONContent } from '@tiptap/core';
import { FileSpreadsheet, FileText } from 'lucide-vue-next';
import { computed } from 'vue';

type EditorMode = 'docs' | 'sheet';

const props = withDefaults(
    defineProps<{
        mode: EditorMode;
        nomor: string;
        title: string;
        agenda: string;
        scheduledAt?: string | null;
        participants: { id: number; name: string }[];
        content: JSONContent;
        creatorName: string;
        approverName: string;
        users?: { id: number; name: string; division_id?: number | null }[];
        divisions?: Division[];
        topics?: { id: number; name: string }[];
        collabUrl?: string | null;
        collabDocument?: string;
        collabToken?: string | null;
        collabUser?: string;
    }>(),
    {
        scheduledAt: null,
        users: () => [],
        divisions: () => [],
        topics: () => [],
        collabUrl: null,
        collabToken: null,
    },
);

const emit = defineEmits<{
    'update:mode': [value: EditorMode];
    'update:nomor': [value: string];
    'update:content': [value: JSONContent];
}>();

const mode = computed({
    get: () => props.mode,
    set: (value: EditorMode) => emit('update:mode', value),
});

const nomor = computed({
    get: () => props.nomor,
    set: (value: string) => emit('update:nomor', value),
});

const content = computed({
    get: () => props.content,
    set: (value: JSONContent) => emit('update:content', value),
});

const participantsText = computed(() => (props.participants.length > 0 ? props.participants.map((participant) => participant.name).join(', ') : '-'));

const agendaLines = computed(() =>
    props.agenda
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line !== ''),
);

const displayedAgenda = computed(() => (agendaLines.value.length > 0 ? agendaLines.value : ['', '', '']));
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-t-lg border border-border bg-card px-3 py-2">
            <p class="flex items-center gap-2 text-sm font-semibold text-foreground">
                <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
                Format notulen
            </p>
            <div class="inline-flex rounded-md border border-border bg-muted p-0.5" role="tablist" aria-label="Mode editor notulen">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'docs'"
                    class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-medium transition-colors duration-fast focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="mode === 'docs' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'docs'"
                >
                    <FileText class="size-3.5" aria-hidden="true" />
                    Docs
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'sheet'"
                    class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-medium transition-colors duration-fast focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="mode === 'sheet' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'sheet'"
                >
                    <FileSpreadsheet class="size-3.5" aria-hidden="true" />
                    Sheet
                </button>
            </div>
        </div>

        <div v-if="mode === 'docs'" class="rounded-b-lg border border-t-0 border-border bg-muted/20 p-3 sm:p-6">
            <div class="mx-auto max-w-[860px] rounded-lg border border-border bg-card p-5 shadow-sm sm:p-8">
                <div class="grid items-center gap-4 border-b border-border pb-4 sm:grid-cols-[140px_1fr_140px]">
                    <img src="/company-logo.png" alt="Derma Semulia Indonesia" class="h-12 w-auto rounded-sm bg-white object-contain p-1 sm:h-14" />
                    <div class="text-center">
                        <h1 class="text-lg font-bold tracking-wide text-foreground sm:text-xl">NOTULENSI RAPAT</h1>
                        <label class="mt-1 flex flex-wrap items-center justify-center gap-1 text-[11px] text-destructive-strong">
                            <span>Nomor Notulensi:</span>
                            <input
                                v-model="nomor"
                                class="w-44 border-b border-dashed border-border bg-transparent px-1 py-0.5 text-center text-[11px] text-foreground focus:border-primary focus:outline-none"
                                placeholder="otomatis saat disimpan"
                                aria-label="Nomor notulensi"
                            />
                        </label>
                    </div>
                    <div class="hidden sm:block"></div>
                </div>

                <table class="mt-4 w-full border-collapse text-xs sm:text-sm">
                    <tbody>
                        <tr>
                            <th class="w-24 border border-border bg-muted p-2 text-left font-semibold">Topik</th>
                            <td class="border border-border p-2">{{ props.title || '-' }}</td>
                        </tr>
                        <tr>
                            <th class="border border-border bg-muted p-2 text-left font-semibold">Tanggal</th>
                            <td class="border border-border p-2">{{ formatDateTime(props.scheduledAt) }}</td>
                        </tr>
                        <tr>
                            <th class="border border-border bg-muted p-2 text-left font-semibold">Peserta</th>
                            <td class="border border-border p-2">{{ participantsText }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 border border-border">
                    <div class="border-b border-border bg-muted p-2 text-xs font-semibold sm:text-sm">Agenda Pembahasan</div>
                    <div
                        v-for="(line, index) in displayedAgenda"
                        :key="`agenda-${index}`"
                        class="flex border-b border-border text-xs last:border-b-0 sm:text-sm"
                    >
                        <span class="w-10 shrink-0 border-r border-border bg-muted/40 p-2 text-center tabular-nums">{{ index + 1 }}</span>
                        <span class="min-h-9 flex-1 p-2">{{ line }}</span>
                    </div>
                </div>

                <div class="mt-4 border border-border">
                    <div class="border-b border-border bg-warning-soft/60 p-2 text-center text-xs font-bold text-warning-strong sm:text-sm">
                        HASIL RAPAT
                    </div>
                    <MeetingEditor
                        v-model="content"
                        :users="props.users"
                        :divisions="props.divisions"
                        :topics="props.topics"
                        :collab-url="props.collabUrl"
                        :collab-document="props.collabDocument"
                        :collab-token="props.collabToken"
                        :collab-user="props.collabUser"
                        placeholder="Tulis hasil rapat di sini..."
                        flat
                    />
                </div>

                <table class="mt-5 w-full border-collapse text-xs sm:text-sm">
                    <tbody>
                        <tr>
                            <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                                Dibuat Oleh:
                                <span class="block text-[10px] font-normal italic text-muted-foreground">(wajib diisi tanda tangan notulis)</span>
                            </th>
                            <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                                Disetujui Oleh:
                                <span class="block text-[10px] font-normal italic text-muted-foreground">
                                    (tanda tangan pimpinan sudah mengetahui &amp; approval)
                                </span>
                            </th>
                        </tr>
                        <tr>
                            <td class="border border-border p-2 align-top">
                                <div class="h-12"></div>
                                <p class="font-semibold">{{ props.creatorName }}</p>
                                <p class="text-xs text-muted-foreground">Notulis</p>
                                <p class="text-[10px] italic text-muted-foreground">(wajib diisi nama penyusun notulen)</p>
                            </td>
                            <td class="border border-border p-2 align-top">
                                <div class="h-12"></div>
                                <p class="font-semibold">{{ props.approverName }}</p>
                                <p class="text-xs text-muted-foreground">Pimpinan Rapat</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="mt-3 text-[11px] italic text-muted-foreground">
                    Notes: Dilarang mengubah format notulensi yang telah disetujui oleh Pimpinan.
                </p>
            </div>
        </div>

        <div v-else class="rounded-b-lg border border-t-0 border-border bg-muted/20 p-3 sm:p-6">
            <div class="mx-auto max-w-[1100px] overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                <div class="flex flex-wrap items-center justify-center gap-3 border-b border-border bg-warning-soft/60 p-3">
                    <img src="/company-logo.png" alt="Derma Semulia Indonesia" class="h-10 w-auto rounded-sm bg-white object-contain p-1" />
                    <div class="text-center">
                        <h1 class="text-base font-bold tracking-wide text-warning-strong sm:text-lg">NOTULENSI RAPAT</h1>
                        <label class="mt-0.5 flex flex-wrap items-center justify-center gap-1 text-[11px] text-destructive-strong">
                            <span>Nomor Notulensi:</span>
                            <input
                                v-model="nomor"
                                class="w-44 border-b border-dashed border-border bg-transparent px-1 py-0.5 text-center text-[11px] text-foreground focus:border-primary focus:outline-none"
                                placeholder="otomatis saat disimpan"
                                aria-label="Nomor notulensi"
                            />
                        </label>
                    </div>
                </div>

                <table class="w-full border-collapse text-xs sm:text-sm">
                    <tbody>
                        <tr>
                            <th class="w-36 border-b border-r border-border bg-muted p-2 text-left font-semibold">Topik</th>
                            <td class="border-b border-border p-2">{{ props.title || '-' }}</td>
                        </tr>
                        <tr>
                            <th class="border-b border-r border-border bg-muted p-2 text-left font-semibold">Tanggal</th>
                            <td class="border-b border-border p-2">{{ formatDateTime(props.scheduledAt) }}</td>
                        </tr>
                        <tr>
                            <th class="border-b border-r border-border bg-muted p-2 text-left font-semibold">Peserta</th>
                            <td class="border-b border-border p-2">{{ participantsText }}</td>
                        </tr>
                        <tr>
                            <th class="border-r border-border bg-muted p-2 text-left align-top font-semibold">Agenda Pembahasan</th>
                            <td class="p-2">
                                <div v-for="(line, index) in displayedAgenda" :key="`sheet-agenda-${index}`" class="py-0.5">
                                    {{ index + 1 }}. {{ line }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="border-y border-border bg-warning-soft/60 p-2 text-center text-xs font-bold text-warning-strong sm:text-sm">
                    HASIL RAPAT
                </div>

                <MinutesSheet v-model="content" />

                <div class="p-3 sm:p-4">
                    <table class="w-full border-collapse text-xs sm:text-sm">
                        <tbody>
                            <tr>
                                <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                                    Dibuat Oleh:
                                    <span class="block text-[10px] font-normal italic text-muted-foreground">(wajib diisi tanda tangan notulis)</span>
                                </th>
                                <th class="w-1/2 border border-border bg-muted p-2 text-left font-semibold">
                                    Disetujui Oleh:
                                    <span class="block text-[10px] font-normal italic text-muted-foreground">
                                        (tanda tangan pimpinan sudah mengetahui &amp; approval)
                                    </span>
                                </th>
                            </tr>
                            <tr>
                                <td class="border border-border p-2 align-top">
                                    <div class="h-12"></div>
                                    <p class="font-semibold">{{ props.creatorName }}</p>
                                    <p class="text-xs text-muted-foreground">Notulis</p>
                                    <p class="text-[10px] italic text-muted-foreground">(wajib diisi nama penyusun notulen)</p>
                                </td>
                                <td class="border border-border p-2 align-top">
                                    <div class="h-12"></div>
                                    <p class="font-semibold">{{ props.approverName }}</p>
                                    <p class="text-xs text-muted-foreground">Pimpinan Rapat</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-3 text-[11px] italic text-muted-foreground">
                        Notes: Dilarang mengubah format notulensi yang telah disetujui oleh Pimpinan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
