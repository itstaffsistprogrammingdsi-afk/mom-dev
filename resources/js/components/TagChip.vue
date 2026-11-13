<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { computed, type HTMLAttributes } from 'vue';

type TagType = 'person' | 'division' | 'topic';

const props = defineProps<{
    kind?: TagType;
    type?: TagType;
    label: string;
    href?: string;
    class?: HTMLAttributes['class'];
}>();

const resolvedKind = computed<TagType>(() => props.kind ?? props.type ?? 'person');
const prefix = computed(() => (resolvedKind.value === 'topic' ? '#' : '@'));
const kindClass = computed(
    () =>
        ({
            person: 'bg-person-surface text-person hover:bg-person-surface/75',
            division: 'bg-division-surface text-division hover:bg-division-surface/75',
            topic: 'bg-topic-surface text-topic hover:bg-topic-surface/75',
        })[resolvedKind.value],
);
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href"
        :class="
            cn(
                'inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium leading-5 transition-colors duration-fast ease-ui',
                href && 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                kindClass,
                props.class,
            )
        "
    >
        <span aria-hidden="true">{{ prefix }}</span>
        <span class="truncate">{{ label }}</span>
    </component>
</template>
