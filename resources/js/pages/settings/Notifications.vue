<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    preferences: {
        email: boolean;
        in_app: boolean;
        categories: {
            meeting: boolean;
            action_item: boolean;
            quick_note: boolean;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Preferensi Notifikasi', href: '/settings/notifikasi' }];

const form = useForm({
    email: props.preferences.email,
    in_app: props.preferences.in_app,
    categories: {
        meeting: props.preferences.categories.meeting,
        action_item: props.preferences.categories.action_item,
        quick_note: props.preferences.categories.quick_note,
    },
});

const categoryLabels = [
    { key: 'meeting' as const, title: 'Notulen & approval', description: 'Finalisasi, permintaan approval, dan resume AI selesai.' },
    { key: 'action_item' as const, title: 'Action item', description: 'Penugasan, pengingat tenggat, dan respons PIC.' },
    { key: 'quick_note' as const, title: 'Quick Note', description: 'Sebutan dan respons pada catatan cepat.' },
];

function submit() {
    form.put(route('notification-preferences.update'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Preferensi Notifikasi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <form class="space-y-6" @submit.prevent="submit">
                <HeadingSmall
                    title="Preferensi Notifikasi"
                    description="Pilih kanal dan jenis pemberitahuan yang ingin Anda terima."
                />

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Kanal</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <label class="flex items-start gap-3">
                            <input v-model="form.in_app" type="checkbox" class="mt-1 size-4 rounded border-input accent-primary" />
                            <span>
                                <span class="block text-sm font-medium">Notifikasi dalam aplikasi</span>
                                <span class="block text-sm text-muted-foreground">Tampil di lonceng dan halaman Notifikasi.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3">
                            <input v-model="form.email" type="checkbox" class="mt-1 size-4 rounded border-input accent-primary" />
                            <span>
                                <span class="block text-sm font-medium">Salinan email</span>
                                <span class="block text-sm text-muted-foreground">Kirim juga ke email terdaftar sesuai preferensi SMTP.</span>
                            </span>
                        </label>

                        <p class="rounded-md border border-border/70 bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                            Peringatan sistem untuk admin (AI gagal, queue bermasalah) selalu tampil di aplikasi.
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Jenis notifikasi</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <label v-for="category in categoryLabels" :key="category.key" class="flex items-start gap-3">
                            <input
                                v-model="form.categories[category.key]"
                                type="checkbox"
                                class="mt-1 size-4 rounded border-input accent-primary"
                            />
                            <span>
                                <span class="block text-sm font-medium">{{ category.title }}</span>
                                <span class="block text-sm text-muted-foreground">{{ category.description }}</span>
                            </span>
                        </label>
                    </CardContent>
                </Card>

                <Button type="submit" :loading="form.processing">Simpan preferensi</Button>
            </form>
        </SettingsLayout>
    </AppLayout>
</template>
