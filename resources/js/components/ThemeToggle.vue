<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Terang', Icon: Sun },
    { value: 'dark', label: 'Gelap', Icon: Moon },
    { value: 'system', label: 'Ikuti sistem', Icon: Monitor },
] as const;

const current = computed(() => options.find((option) => option.value === appearance.value) ?? options[2]);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" :title="`Tema: ${current.label}`" aria-label="Pilih tema">
                <component :is="current.Icon" class="size-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                class="flex items-center gap-2"
                @click="updateAppearance(option.value)"
            >
                <component :is="option.Icon" class="size-4" />
                {{ option.label }}
                <span v-if="appearance === option.value" class="ml-auto text-xs text-muted-foreground">Aktif</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
