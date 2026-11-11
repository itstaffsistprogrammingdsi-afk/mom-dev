<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Hapus akun" description="Hapus akun Anda secara permanen." />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Perhatian</p>
                <p class="text-sm">Tindakan ini tidak dapat dibatalkan. Akun yang masih memiliki notulen tidak dapat dihapus.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Hapus akun</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Yakin ingin menghapus akun Anda?</DialogTitle>
                            <DialogDescription>
                                Setelah dihapus, akun tidak dapat dipulihkan. Masukkan kata sandi untuk mengonfirmasi bahwa Anda ingin menghapus akun
                                ini secara permanen.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Kata sandi</Label>
                            <Input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="secondary" @click="closeModal">Batal</Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="form.processing">Hapus akun</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
