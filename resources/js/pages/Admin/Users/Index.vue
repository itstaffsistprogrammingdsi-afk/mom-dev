<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { roleLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type UserRole } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Search, Users } from 'lucide-vue-next';
import { ref } from 'vue';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    job_title: string | null;
    is_active: boolean;
    division_id: number | null;
    division?: Division | null;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    users: Paginated<AdminUser>;
    divisions: Division[];
    roles: { value: string; label: string }[];
    filters: { search?: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pengguna', href: '/admin/pengguna' }];
const search = ref(props.filters.search ?? '');

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'member',
    division_id: '',
    job_title: '',
    is_active: true,
});

function createUser() {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name', 'email', 'password', 'job_title'),
    });
}

function applySearch() {
    router.get(route('admin.users.index'), { search: search.value }, { preserveState: true, replace: true });
}

function updateUser(user: AdminUser, field: 'role' | 'division_id' | 'is_active', value: string | number | boolean) {
    router.patch(
        route('admin.users.update', user.id),
        {
            role: user.role,
            division_id: user.division_id,
            job_title: user.job_title,
            is_active: user.is_active,
            [field]: value,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-section p-page lg:p-page-lg">
            <PageHeader title="Pengguna" description="Atur peran, divisi, dan status aktif pengguna.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.roles.index')">Peran & Akses</Link>
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle>Tambah pengguna</CardTitle>
                    <CardDescription>Buat akun baru dan tentukan peran awalnya.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" @submit.prevent="createUser">
                        <div class="space-y-1.5">
                            <Label for="new-user-name">Nama</Label>
                            <Input id="new-user-name" v-model="createForm.name" required placeholder="Nama lengkap" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="new-user-email">Email</Label>
                            <Input id="new-user-email" v-model="createForm.email" required type="email" placeholder="nama@perusahaan.com" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="new-user-password">Kata sandi</Label>
                            <Input id="new-user-password" v-model="createForm.password" required type="password" placeholder="Kata sandi awal" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="new-user-role">Peran</Label>
                            <Select id="new-user-role" v-model="createForm.role">
                                <option v-for="role in props.roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="new-user-division">Divisi</Label>
                            <Select id="new-user-division" v-model="createForm.division_id">
                                <option value="">Tanpa divisi</option>
                                <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">
                                    {{ division.name }}
                                </option>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="new-user-job">Jabatan</Label>
                            <Input id="new-user-job" v-model="createForm.job_title" placeholder="Jabatan" />
                        </div>
                        <div class="flex items-end sm:col-span-2 xl:col-span-3">
                            <Button type="submit" :loading="createForm.processing">
                                <Plus class="size-4" aria-hidden="true" />
                                Tambah pengguna
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <form class="flex max-w-md items-end gap-2" @submit.prevent="applySearch">
                <div class="flex-1 space-y-1.5">
                    <Label for="user-search">Cari pengguna</Label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-subtle-foreground"
                            aria-hidden="true"
                        />
                        <Input id="user-search" v-model="search" placeholder="Nama atau email" class="pl-9" />
                    </div>
                </div>
                <Button type="submit">Cari</Button>
            </form>

            <EmptyState
                v-if="props.users.data.length === 0"
                :icon="Users"
                title="Tidak ada pengguna"
                description="Ubah kata kunci atau tambahkan pengguna baru."
            />

            <div v-else class="min-w-0 overflow-x-auto rounded-lg border border-border/80 bg-card shadow-soft-sm">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="border-b border-border/60 text-left">
                        <tr class="text-xs font-medium text-subtle-foreground">
                            <th class="p-3">Nama</th>
                            <th class="p-3">Divisi</th>
                            <th class="p-3">Peran</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-for="user in props.users.data" :key="user.id" class="transition-colors hover:bg-muted/20">
                            <td class="p-3">
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                            </td>
                            <td class="p-3">
                                <Select
                                    :model-value="user.division_id ?? ''"
                                    class="h-8 text-xs"
                                    :aria-label="`Divisi ${user.name}`"
                                    @update:model-value="updateUser(user, 'division_id', $event ? Number($event) : '')"
                                >
                                    <option value="">Tanpa divisi</option>
                                    <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">
                                        {{ division.name }}
                                    </option>
                                </Select>
                            </td>
                            <td class="p-3">
                                <Select
                                    :model-value="user.role"
                                    class="h-8 text-xs"
                                    :aria-label="`Peran ${user.name}`"
                                    @update:model-value="updateUser(user, 'role', String($event))"
                                >
                                    <option v-for="(label, value) in roleLabels" :key="value" :value="value">{{ label }}</option>
                                </Select>
                            </td>
                            <td class="p-3">
                                <button
                                    type="button"
                                    class="rounded-full outline-none focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2"
                                    :aria-pressed="user.is_active"
                                    :aria-label="`Ubah status ${user.name}`"
                                    @click="updateUser(user, 'is_active', !user.is_active)"
                                >
                                    <Badge :variant="user.is_active ? 'success' : 'neutral'">
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Menampilkan {{ props.users.from ?? 0 }}–{{ props.users.to ?? 0 }} dari {{ props.users.total }} pengguna
                </p>
                <Pagination :links="props.users.links" />
            </div>
        </div>
    </AppLayout>
</template>
