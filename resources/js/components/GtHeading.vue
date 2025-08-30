<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex">
        <div class="mb-8 space-y-0.5">
            <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="flex-1">
            <div class="flex justify-end gap-2 items-center">
                <slot />
            </div>
        </div>
    </div>
</template>
