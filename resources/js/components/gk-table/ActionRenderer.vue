<template>
    <component :is="getComponent(action.component || 'Link')" :action="action" v-if="action.position === position" />
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
import { ActionPosition, ActionProps } from '../../types/field';

defineProps<ActionProps & { position: ActionPosition, form?: any }>();

const getComponent = (component: string) => {
    return `Gt${component}`;
};
</script>