<template>
    <component :is="getComponent(action.component)" :action="action" />
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
defineProps<{
    action: {
        component: string;
        [key: string]: any;
    };
}>();

const getComponent = (component: string) => {
    return `Gt${component}`;
};
</script>