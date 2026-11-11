<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface AvatarUser {
    id: number | string;
    name: string;
    avatar?: string | null;
}

const props = withDefaults(
    defineProps<{
        users: AvatarUser[];
        max?: number;
        size?: 'xs' | 'sm' | 'md';
        class?: string;
    }>(),
    {
        max: 4,
        size: 'sm',
    },
);

const sizeClasses = {
    xs: 'h-6 w-6 text-[10px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
};

const visibleUsers = computed(() => props.users.slice(0, props.max));
const remaining = computed(() => Math.max(0, props.users.length - props.max));
const sizeClass = computed(() => sizeClasses[props.size]);
</script>

<template>
    <div :class="cn('flex items-center', props.class)" role="group" :aria-label="`${props.users.length} peserta`">
        <div class="flex -space-x-2">
            <Avatar v-for="user in visibleUsers" :key="user.id" :class="cn(sizeClass, 'ring-2 ring-card')">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback class="bg-secondary font-medium text-secondary-foreground">
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
            <span
                v-if="remaining > 0"
                :class="
                    cn(sizeClass, 'inline-flex items-center justify-center rounded-full bg-muted font-medium text-muted-foreground ring-2 ring-card')
                "
                :aria-label="`${remaining} peserta lain`"
            >
                +{{ remaining }}
            </span>
        </div>
    </div>
</template>
