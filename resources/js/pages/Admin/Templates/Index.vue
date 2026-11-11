<script setup lang="ts">
import DocumentTypeBadge from '@/components/DocumentTypeBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { meetingTypeLabels } from '@/lib/labels';
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
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Template Dokumen" description="Kelola struktur notulen, proposal, laporan, brosur, dan dokumen lain." />

            <Card>
                <CardHeader><CardTitle class="text-base">Tambah template</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <form class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="submit">
                        <div class="space-y-1.5">
                            <Label for="template-name">Nama template</Label>
                            <Input id="template-name" v-model="form.name" required placeholder="Nama template" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="template-document-type">Jenis dokumen</Label>
                            <Select id="template-document-type" v-model="form.document_type">
                                <option v-for="type in props.documentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="template-meeting-type">Jenis rapat</Label>
                            <Select id="template-meeting-type" v-model="form.meeting_type">
                                <option v-for="type in props.meetingTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="template-description">Deskripsi</Label>
                            <Input id="template-description" v-model="form.description" placeholder="Deskripsi singkat" />
                        </div>
                        <div class="flex items-end sm:col-span-2 xl:col-span-4">
                            <Button type="submit" :loading="form.processing">
                                <Plus class="size-4" aria-hidden="true" />
                                Buat template
                            </Button>
                        </div>
                    </form>
                    <p v-if="selectedType" class="text-xs text-muted-foreground">
                        {{ selectedType.description }} Template akan dibuat dengan struktur dasar yang dapat Anda sunting.
                    </p>
                    <p v-if="form.errors.name" class="text-xs font-medium text-destructive-strong">{{ form.errors.name }}</p>
                </CardContent>
            </Card>

            <Card class="min-w-0"
                ><CardHeader><CardTitle class="text-base">Daftar template</CardTitle></CardHeader
                ><CardContent class="min-w-0 overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[880px] text-sm">
                            <thead class="border-b border-border/60 text-left text-xs font-medium text-subtle-foreground">
                                <tr>
                                    <th class="py-2 pr-3">Nama</th>
                                    <th class="py-2 pr-3">Jenis dokumen</th>
                                    <th class="py-2 pr-3">Jenis rapat</th>
                                    <th class="py-2 pr-3">Struktur</th>
                                    <th class="py-2 pr-3">Status</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="template in props.templates" :key="template.id" class="transition-colors hover:bg-muted/20">
                                    <td class="py-3 pr-3">
                                        <p class="font-medium">{{ template.name }}</p>
                                        <p class="max-w-xs text-xs text-muted-foreground">{{ template.description ?? '-' }}</p>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <DocumentTypeBadge :type="template.document_type" />
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
                                        <Badge :variant="template.is_active ? 'success' : 'neutral'">
                                            {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </Badge>
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
