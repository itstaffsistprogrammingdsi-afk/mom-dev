<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, X } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

interface Template {
    id: number;
    name: string;
    meeting_type: string;
    description: string | null;
    is_active: boolean;
}

const props = defineProps<{
    templates: Template[];
    types: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Template Notulen', href: '/admin/template' }];
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    meeting_type: 'weekly',
    description: '',
    is_active: true,
});

const editForm = reactive({
    name: '',
    meeting_type: 'weekly',
    description: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.templates.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'description'),
    });
}

function startEdit(template: Template) {
    editingId.value = template.id;
    Object.assign(editForm, {
        name: template.name,
        meeting_type: template.meeting_type,
        description: template.description ?? '',
        is_active: template.is_active,
    });
}

function saveEdit(template: Template) {
    router.patch(route('admin.templates.update', template.id), { ...editForm }, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function toggle(template: Template) {
    router.patch(route('admin.templates.toggle', template.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Template Notulen" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Template Notulen" description="Kelola struktur standar notulen per jenis rapat." />

            <Card>
                <CardHeader><CardTitle class="text-base">Tambah template</CardTitle></CardHeader>
                <CardContent>
                    <form class="grid gap-2 md:grid-cols-5" @submit.prevent="submit">
                        <input v-model="form.name" required placeholder="Nama template" class="h-9 rounded-md border bg-background px-3 text-sm md:col-span-2" />
                        <select v-model="form.meeting_type" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-for="type in props.types" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <input v-model="form.description" placeholder="Deskripsi" class="h-9 rounded-md border bg-background px-3 text-sm" />
                        <Button type="submit">
                            <Plus class="size-4" />
                            Tambah
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle class="text-base">Daftar template</CardTitle></CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="py-2 pr-3">Nama</th>
                                    <th class="py-2 pr-3">Jenis</th>
                                    <th class="py-2 pr-3">Deskripsi</th>
                                    <th class="py-2 pr-3">Status</th>
                                    <th class="py-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="template in props.templates" :key="template.id">
                                    <td class="py-3 pr-3">
                                        <input v-if="editingId === template.id" v-model="editForm.name" class="h-8 w-full rounded-md border bg-background px-2 text-sm" />
                                        <span v-else class="font-medium">{{ template.name }}</span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <select v-if="editingId === template.id" v-model="editForm.meeting_type" class="h-8 rounded-md border bg-background px-2 text-xs">
                                            <option v-for="type in props.types" :key="type.value" :value="type.value">{{ type.label }}</option>
                                        </select>
                                        <span v-else>{{ props.types.find((type) => type.value === template.meeting_type)?.label ?? template.meeting_type }}</span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input v-if="editingId === template.id" v-model="editForm.description" class="h-8 w-full rounded-md border bg-background px-2 text-sm" />
                                        <span v-else class="text-muted-foreground">{{ template.description ?? '—' }}</span>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <span class="rounded-full px-2 py-1 text-xs" :class="template.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'">
                                            {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-1">
                                            <template v-if="editingId === template.id">
                                                <Button size="sm" @click="saveEdit(template)">Simpan</Button>
                                                <Button size="sm" variant="ghost" @click="editingId = null"><X class="size-4" /></Button>
                                            </template>
                                            <template v-else>
                                                <Button size="sm" variant="ghost" @click="startEdit(template)"><Pencil class="size-4" /></Button>
                                                <Button size="sm" variant="outline" @click="toggle(template)">
                                                    {{ template.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </Button>
                                            </template>
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
