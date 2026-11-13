<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { computed, type HTMLAttributes } from 'vue';

type Person = { id: number | string; name: string; avatar?: string | null };
type AvatarSize = 'xs' | 'sm' | 'md';

const props = withDefaults(
    defineProps<{
        people?: Person[];
        users?: Person[];
        max?: number;
        size?: AvatarSize;
        class?: HTMLAttributes['class'];
    }>(),
    {
        max: 4,
        size: 'sm',
    },
);

const people = computed(() => props.people ?? props.users ?? []);
const visiblePeople = computed(() => people.value.slice(0, Math.max(0, props.max)));
const extraCount = computed(() => Math.max(0, people.value.length - visiblePeople.value.length));

const sizeClasses: Record<AvatarSize, string> = {
    xs: 'size-6 text-[11px]',
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
};

const sizeClass = computed(() => sizeClasses[props.size]);

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <div
        v-if="people.length"
        role="group"
        :class="cn('flex items-center -space-x-2', props.class)"
        :aria-label="`Peserta: ${people.map((person) => person.name).join(', ')}`"
    >
        <Avatar v-for="person in visiblePeople" :key="person.id" :size="size" class="ring-2 ring-card" :title="person.name">
            <AvatarImage v-if="person.avatar" :src="person.avatar" :alt="person.name" />
            <AvatarFallback class="bg-secondary font-semibold text-secondary-foreground">{{ initials(person.name) }}</AvatarFallback>
        </Avatar>
        <span
            v-if="extraCount"
            :class="
                cn(
                    sizeClass,
                    'relative inline-flex shrink-0 items-center justify-center rounded-full bg-secondary font-semibold text-secondary-foreground ring-2 ring-card',
                )
            "
            :title="`${extraCount} peserta lainnya`"
        >
            +{{ extraCount }}
        </span>
    </div>
</template>
