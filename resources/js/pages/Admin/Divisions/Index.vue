<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

interface DivisionRow {
    id: number;
    name: string;
    description: string | null;
    users_count: number;
    meetings_count: number;
    head?: { id: number; name: string } | null;
}

const props = defineProps<{
    divisions: DivisionRow[];
    users: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Divisi', href: '/admin/divisi' }];

const form = useForm({
    name: '',
    description: '',
    head_id: '',
});

function submit() {
    form.post(route('admin.divisions.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Divisi" description="Kelola daftar divisi dan kepala divisi." />

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="h-fit">
                    <CardHeader><CardTitle class="text-base">Tambah divisi</CardTitle></CardHeader>
                    <CardContent>
                        <form class="space-y-3" @submit.prevent="submit">
                            <div class="space-y-1">
                                <Label for="division-name">Nama</Label>
                                <Input id="division-name" v-model="form.name" placeholder="Contoh: Teknologi Informasi" />
                                <p v-if="form.errors.name" class="text-xs text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label for="division-description">Deskripsi</Label>
                                <textarea
                                    id="division-description"
                                    v-model="form.description"
                                    rows="2"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="space-y-1">
                                <Label for="division-head">Kepala divisi</Label>
                                <select id="division-head" v-model="form.head_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                    <option value="">Belum ditentukan</option>
                                    <option v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                                </select>
                            </div>
                            <Button type="submit" class="w-full" :disabled="form.processing">Simpan divisi</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardContent class="overflow-x-auto p-0">
                        <table class="w-full min-w-[520px] text-sm">
                            <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="p-3">Divisi</th>
                                    <th class="p-3">Kepala</th>
                                    <th class="p-3">Anggota</th>
                                    <th class="p-3">Notulen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="division in props.divisions" :key="division.id">
                                    <td class="p-3">
                                        <p class="font-medium">{{ division.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ division.description || '—' }}</p>
                                    </td>
                                    <td class="p-3">{{ division.head?.name ?? '—' }}</td>
                                    <td class="p-3">{{ division.users_count }}</td>
                                    <td class="p-3">{{ division.meetings_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
