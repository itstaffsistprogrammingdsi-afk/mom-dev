<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { computed } from 'vue';

type Person = { id: number | string; name: string; avatar?: string | null };

const props = withDefaults(defineProps<{ people: Person[]; max?: number }>(), { max: 4 });
const visiblePeople = computed(() => props.people.slice(0, Math.max(0, props.max)));
const extraCount = computed(() => Math.max(0, props.people.length - visiblePeople.value.length));

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
    <div v-if="people.length" role="group" class="flex items-center -space-x-2" :aria-label="`Peserta: ${people.map((person) => person.name).join(', ')}`">
        <Avatar v-for="person in visiblePeople" :key="person.id" size="xs" class="ring-2 ring-card" :title="person.name">
            <AvatarImage v-if="person.avatar" :src="person.avatar" :alt="person.name" />
            <AvatarFallback class="bg-secondary font-semibold text-secondary-foreground">{{ initials(person.name) }}</AvatarFallback>
        </Avatar>
        <span
            v-if="extraCount"
            class="relative inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-secondary text-xs font-semibold text-secondary-foreground ring-2 ring-card"
            :title="`${extraCount} peserta lainnya`"
        >
            +{{ extraCount }}
        </span>
    </div>
</template>
