<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { documentTypeClasses, documentTypeLabels, meetingTypeLabels } from '@/lib/labels';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { FilePenLine, Plus } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Template {
    id: number;
    name: string;
    document_type: string;
    meeting_type: string;
    description: string | null;
    sections: string[] | null;
    is_active: boolean;
}

const props = defineProps<{
    templates: Template[];
    documentTypes: { value: string; label: string; description: string }[];
    meetingTypes: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Template Dokumen', href: '/admin/template' }];

const form = useForm({
    name: '',
    document_type: 'meeting',
    meeting_type: 'weekly',
    description: '',
    is_active: true,
});

const selectedType = computed(() => props.documentTypes.find((type) => type.value === form.document_type));

watch(
    () => form.document_type,
    (type) => {
        if (type !== 'meeting') {
            form.meeting_type = 'other';
        }
    },
);

function submit() {
    form.post(route('admin.templates.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'description'),
    });
}

function toggle(template: Template) {
    router.patch(route('admin.templates.toggle', template.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Template Dokumen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Template Dokumen" description="Kelola struktur notulen, proposal, laporan, brosur, dan dokumen lain." />

            <Card>
                <CardHeader><CardTitle class="text-base">Tambah template</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <form class="grid gap-2 md:grid-cols-6" @submit.prevent="submit">
                        <input
                            v-model="form.name"
                            required
                            placeholder="Nama template"
                            class="h-9 rounded-md border bg-background px-3 text-sm md:col-span-2"
                        />
                        <select v-model="form.document_type" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <select v-model="form.meeting_type" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-for="type in props.meetingTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <input v-model="form.description" placeholder="Deskripsi singkat" class="h-9 rounded-md border bg-background px-3 text-sm" />
                        <Button type="submit" :disabled="form.processing">
                            <Plus class="size-4" />
                            Buat
                        </Button>
                    </form>
                    <p v-if="selectedType" class="text-xs text-muted-foreground">
                        {{ selectedType.description }} Template akan dibuat dengan struktur dasar yang dapat Anda sunting.
                    </p>
                    <p v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle class="text-base">Daftar template</CardTitle></CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[880px] text-sm">
                            <thead class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="py-2 pr-3">Nama</th>
                                    <th class="py-2 pr-3">Jenis dokumen</th>
                                    <th class="py-2 pr-3">Jenis rapat</th>
                                    <th class="py-2 pr-3">Struktur</th>
                                    <th class="py-2 pr-3">Status</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="template in props.templates" :key="template.id">
                                    <td class="py-3 pr-3">
                                        <p class="font-medium">{{ template.name }}</p>
                                        <p class="max-w-xs text-xs text-muted-foreground">{{ template.description ?? '—' }}</p>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium" :class="documentTypeClasses[template.document_type]">
                                            {{ documentTypeLabels[template.document_type] ?? template.document_type }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3 text-muted-foreground">
                                        {{ meetingTypeLabels[template.meeting_type] ?? template.meeting_type }}
                                    </td>
                                    <td class="py-3 pr-3 text-xs text-muted-foreground">
                                        {{ template.sections?.length ?? 0 }} bagian
                                        <span v-if="template.sections?.length" class="block max-w-xs truncate">
                                            {{ template.sections.join(' · ') }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs"
                                            :class="template.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'"
                                        >
                                            {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-1">
                                            <Button size="sm" variant="outline" as-child>
                                                <Link :href="route('admin.templates.edit', template.id)">
                                                    <FilePenLine class="size-4" />
                                                    Sunting isi
                                                </Link>
                                            </Button>
                                            <Button size="sm" variant="ghost" @click="toggle(template)">
                                                {{ template.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
