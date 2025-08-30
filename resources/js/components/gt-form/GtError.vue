<template>
    <p v-if="props.error" :id="`${props.id}-error`" class="text-sm font-medium text-destructive">
        {{ props.error }}
    </p>
</template>
<script setup lang="ts">


const props = defineProps<{
    id: string;
    error?: string;
}>()
</script>