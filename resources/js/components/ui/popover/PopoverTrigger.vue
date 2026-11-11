<script setup lang="ts">
import { PopoverTrigger } from 'radix-vue';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <PopoverTrigger v-bind="$attrs">
        <slot />
    </PopoverTrigger>
</template>
