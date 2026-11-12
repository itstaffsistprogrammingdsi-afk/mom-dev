<script setup lang="ts">
import { cn } from '@/lib/utils';
import { LoaderCircle } from 'lucide-vue-next';
import { Primitive, type PrimitiveProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';
import { buttonVariants, type ButtonVariants } from '.';

interface Props extends PrimitiveProps {
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    loading?: boolean;
    disabled?: boolean;
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
        :disabled="!asChild && (disabled || loading) ? true : undefined"
        :aria-busy="loading || undefined"
        :data-loading="loading || undefined"
        :class="cn(buttonVariants({ variant, size }), loading && 'pointer-events-none opacity-70', props.class)"
    >
        <LoaderCircle v-if="loading && !asChild" class="animate-spin motion-reduce:animate-none" aria-hidden="true" />
        <slot />
    </Primitive>
</template>
