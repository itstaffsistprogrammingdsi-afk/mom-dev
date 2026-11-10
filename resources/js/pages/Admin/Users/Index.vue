<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { roleLabels } from '@/lib/labels';
import { type BreadcrumbItem, type Division, type UserRole } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
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
        <div class="flex flex-col gap-6 p-4">
            <PageHeader title="Pengguna" description="Atur peran, divisi, dan status aktif pengguna.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.roles.index')">Peran & Akses</Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-2 rounded-lg border bg-card p-3 md:grid-cols-7" @submit.prevent="createUser">
                <input v-model="createForm.name" required placeholder="Nama" class="h-9 rounded-md border bg-background px-3 text-sm" />
                <input v-model="createForm.email" required type="email" placeholder="Email" class="h-9 rounded-md border bg-background px-3 text-sm" />
                <input v-model="createForm.password" required type="password" placeholder="Password" class="h-9 rounded-md border bg-background px-3 text-sm" />
                <select v-model="createForm.role" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option v-for="role in props.roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                </select>
                <select v-model="createForm.division_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                    <option value="">Tanpa divisi</option>
                    <option v-for="division in props.divisions" :key="division.id" :value="String(division.id)">{{ division.name }}</option>
                </select>
                <input v-model="createForm.job_title" placeholder="Jabatan" class="h-9 rounded-md border bg-background px-3 text-sm" />
                <Button type="submit">
                    <Plus class="size-4" />
                    Tambah
                </Button>
            </form>

            <form class="flex max-w-md gap-2" @submit.prevent="applySearch">
                <div class="relative flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari nama atau email…" class="pl-8" />
                </div>
                <Button type="submit">Cari</Button>
            </form>

            <div class="overflow-x-auto rounded-lg border bg-card">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Divisi</th>
                            <th class="p-3">Peran</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="user in props.users.data" :key="user.id">
                            <td class="p-3">
                                <p class="font-medium">{{ user.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                            </td>
                            <td class="p-3">
                                <select
                                    :value="user.division_id ?? ''"
                                    class="h-8 rounded-md border bg-background px-2 text-xs"
                                    @change="
                                        updateUser(
                                            user,
                                            'division_id',
                                            ($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : '',
                                        )
                                    "
                                >
                                    <option value="">Tanpa divisi</option>
                                    <option v-for="division in props.divisions" :key="division.id" :value="division.id">{{ division.name }}</option>
                                </select>
                            </td>
                            <td class="p-3">
                                <select
                                    :value="user.role"
                                    class="h-8 rounded-md border bg-background px-2 text-xs"
                                    @change="updateUser(user, 'role', ($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="(label, value) in roleLabels" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </td>
                            <td class="p-3">
                                <button
                                    type="button"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700'"
                                    @click="updateUser(user, 'is_active', !user.is_active)"
                                >
                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
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
