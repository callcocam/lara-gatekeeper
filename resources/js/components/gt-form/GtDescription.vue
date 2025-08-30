<template>
    <p v-if="props.description && !props.error" class="text-sm text-muted-foreground">
        {{ props.description }}
    </p>
</template>
<script setup lang="ts">

const props = defineProps<{
    description?: string;
    error?: string;
}>()
</script>