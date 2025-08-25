<template>
    <component :is="getComponent(action.component)" :action="action" />
</template>
<script setup lang="ts">
import { defineProps } from 'vue';

interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        url?: string;
        iconPosition?: string;
        size: "default" | "sm" | "lg" | "icon" | null | undefined;
        variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
        confirm?: {
            title: string;
            description: string;
        };
        [key: string]: any;
    };
}

const props = defineProps<ActionProps>();
 
const getComponent = (component: string) => {
    return `Gt${component}`;
};
</script>