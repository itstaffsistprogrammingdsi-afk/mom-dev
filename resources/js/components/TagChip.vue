<script setup lang="ts">
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { Building2, Hash, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

type TagType = 'person' | 'division' | 'topic';

const props = withDefaults(
    defineProps<{
        type: TagType;
        label: string;
        href?: string | null;
        class?: string;
    }>(),
    {
        href: null,
    },
);

const icons = {
    person: UserRound,
    division: Building2,
    topic: Hash,
};

const variants: Record<TagType, BadgeVariants['variant']> = {
    person: 'tag-person',
    division: 'tag-division',
    topic: 'tag-topic',
};

const icon = computed(() => icons[props.type]);
const variant = computed(() => variants[props.type]);
const clickable = computed(() => Boolean(props.href));
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href || undefined"
        :class="
            cn(
                'inline-flex rounded-full outline-none',
                clickable && 'focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2',
                props.class,
            )
        "
    >
        <Badge
            :variant="variant"
            :class="
                cn(
                    'gap-1.5 py-1 transition-[background-color,border-color,color] duration-fast ease-out-soft',
                    clickable && 'hover:brightness-[0.97]',
                )
            "
        >
            <component :is="icon" class="size-3.5" aria-hidden="true" />
            {{ label }}
        </Badge>
    </component>
</template>
