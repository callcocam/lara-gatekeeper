<template>
    <component :is="getComponent(action.component)" :action="action" />
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
import { ActionProps } from '../../types/field';
 

const props = defineProps<ActionProps>();
 
const getComponent = (component: string) => {
    return `Gt${component}`;
};
</script>