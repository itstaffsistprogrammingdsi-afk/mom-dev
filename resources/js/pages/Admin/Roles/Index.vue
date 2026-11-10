<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { roleLabels } from '@/lib/labels';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Check, Minus } from 'lucide-vue-next';

interface Role {
    value: string;
    label: string;
    description: string;
}

const props = defineProps<{
    roles: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Peran & Akses', href: '/admin/peran' }];

const capabilities: { label: string; roles: string[] }[] = [
    { label: 'Melihat dashboard dan dokumen sesuai hak akses', roles: ['admin', 'notulis', 'member', 'reader'] },
    { label: 'Membuat dan menyunting draf dokumen', roles: ['admin', 'notulis'] },
    { label: 'Memfinalkan dokumen', roles: ['admin', 'notulis'] },
    { label: 'Menyunting dokumen setelah final', roles: ['admin'] },
    { label: 'Mengelola action item pada dokumen draf', roles: ['admin', 'notulis'] },
    { label: 'Memperbarui status tugas sendiri sebagai PIC', roles: ['admin', 'notulis', 'member'] },
    { label: 'Menjalankan resume AI dan menerapkannya', roles: ['admin', 'notulis'] },
    { label: 'Mengelola pengguna, divisi, topik, dan template', roles: ['admin'] },
    { label: 'Melihat audit log', roles: ['admin'] },
    { label: 'Menerima notifikasi saat di-tag atau diberi tugas', roles: ['admin', 'notulis', 'member', 'reader'] },
];
</script>

<template>
    <Head title="Peran & Akses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <PageHeader
                title="Peran & Hak Akses"
                description="Empat peran dengan tanggung jawab berbeda. Hak akses tetap mengikuti tingkat kerahasiaan dokumen."
            />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="role in props.roles" :key="role.value">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base">{{ role.label }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ role.description }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader><CardTitle class="text-base">Matriks hak akses</CardTitle></CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="border-b text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="py-3 pr-4">Kemampuan</th>
                                    <th v-for="role in props.roles" :key="role.value" class="py-3 px-3 text-center">
                                        {{ roleLabels[role.value] ?? role.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="capability in capabilities" :key="capability.label">
                                    <td class="py-3 pr-4">{{ capability.label }}</td>
                                    <td v-for="role in props.roles" :key="role.value" class="py-3 px-3 text-center">
                                        <span
                                            class="inline-flex size-6 items-center justify-center rounded-full"
                                            :class="capability.roles.includes(role.value) ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'"
                                            :aria-label="capability.roles.includes(role.value) ? 'Diizinkan' : 'Tidak diizinkan'"
                                        >
                                            <Check v-if="capability.roles.includes(role.value)" class="size-4" aria-hidden="true" />
                                            <Minus v-else class="size-4" aria-hidden="true" />
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Hak baca tetap mengikuti tingkat kerahasiaan: terbuka, divisi, terbatas, atau rahasia. Admin dapat melihat seluruh dokumen.
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Kepala divisi tidak memakai peran terpisah; ia memakai peran Notulis atau Anggota dan ditetapkan sebagai kepala divisi pada data divisi.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
