<template>
    <div class="flex items-center space-x-2">
        <component :is="filter.component" :filter="filter" v-model="queryParams[filter.name]"
            @update:modelValue="$emit('update:modelValue', { name: filter.name, value: $event })" />
    </div>
</template>
<script lang="ts" setup>
import { defineProps } from 'vue';

interface FilterProps {
    queryParams: Record<string, any>;
    filter: {
        id: string;
        name: string;
        component: any;
        options: Record<string, any>;
        modelValue: string | number;
    };
}

const props = defineProps<FilterProps>();

const emit = defineEmits(['update:modelValue']);

</script>