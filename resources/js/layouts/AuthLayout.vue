<script setup lang="ts">
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';

defineProps<{
    title?: string;
    description?: string;
    kicker?: string;
}>();
</script>

<template>
    <AuthSplitLayout :title="title" :description="description" :kicker="kicker">
        <slot />
    </AuthSplitLayout>
</template>
