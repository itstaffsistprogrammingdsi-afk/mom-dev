<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-vue-next';
import { Primitive, type PrimitiveProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';
import { buttonVariants, type ButtonVariants } from '.';

interface Props extends PrimitiveProps {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    loading?: boolean;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    loading: false,
});
</script>

<template>
    <Primitive
        :as="as"
        :as-child="asChild"
        :disabled="!asChild && loading ? true : undefined"
        :aria-busy="loading || undefined"
        :data-loading="loading || undefined"
        :class="cn(buttonVariants({ variant, size }), loading && 'pointer-events-none opacity-70', props.class)"
    >
        <Loader2 v-if="loading" class="animate-spin" aria-hidden="true" />
        <slot />
    </Primitive>
</template>
