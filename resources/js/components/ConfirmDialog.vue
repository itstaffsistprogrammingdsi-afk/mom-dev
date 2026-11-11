<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertTriangle, Info } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        variant?: 'danger' | 'default';
        loading?: boolean;
    }>(),
    {
        description: '',
        confirmLabel: 'Lanjutkan',
        cancelLabel: 'Batal',
        variant: 'danger',
        loading: false,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

const icon = computed(() => (props.variant === 'danger' ? AlertTriangle : Info));
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <div class="flex items-start gap-4">
                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-full"
                    :class="props.variant === 'danger' ? 'bg-destructive-soft text-destructive-strong' : 'bg-info-soft text-info-strong'"
                >
                    <component :is="icon" class="size-5" aria-hidden="true" />
                </div>
                <DialogHeader class="flex-1 pt-0.5">
                    <DialogTitle>{{ props.title }}</DialogTitle>
                    <DialogDescription v-if="props.description">{{ props.description }}</DialogDescription>
                </DialogHeader>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" :disabled="props.loading" @click="emit('update:open', false)">
                    {{ props.cancelLabel }}
                </Button>
                <Button
                    type="button"
                    :variant="props.variant === 'danger' ? 'destructive' : 'default'"
                    :loading="props.loading"
                    @click="emit('confirm')"
                >
                    {{ props.confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
