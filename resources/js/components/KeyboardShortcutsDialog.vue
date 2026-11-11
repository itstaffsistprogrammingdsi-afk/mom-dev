<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const shortcuts = [
    { keys: ['Ctrl', 'Shift', 'F'], label: 'Aktifkan atau keluar dari mode fokus editor' },
    { keys: ['Esc'], label: 'Tutup dialog, popover, atau mode fokus' },
    { keys: ['@'], label: 'Sisipkan mention orang atau divisi' },
    { keys: ['#'], label: 'Sisipkan tag topik' },
    { keys: ['/'], label: 'Buka slash command di awal baris' },
    { keys: ['Tab'], label: 'Pindah fokus ke kontrol berikutnya' },
    { keys: ['Enter'], label: 'Pilih hasil saran mention atau slash command' },
];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Pintasan keyboard</DialogTitle>
                <DialogDescription>Gunakan pintasan ini untuk bekerja lebih cepat tanpa mouse.</DialogDescription>
            </DialogHeader>

            <ul class="divide-y divide-border/60">
                <li v-for="shortcut in shortcuts" :key="shortcut.label" class="flex items-start justify-between gap-4 py-3">
                    <span class="text-sm leading-relaxed text-muted-foreground">{{ shortcut.label }}</span>
                    <span class="flex shrink-0 items-center gap-1">
                        <kbd
                            v-for="key in shortcut.keys"
                            :key="key"
                            class="inline-flex min-w-7 items-center justify-center rounded-sm border border-border bg-muted px-1.5 py-0.5 font-mono text-[10px] font-medium text-secondary-foreground"
                        >
                            {{ key }}
                        </kbd>
                    </span>
                </li>
            </ul>

            <DialogFooter>
                <Button type="button" @click="emit('update:open', false)">Mengerti</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
