<template>
    <div class="flex items-center space-x-2">
        <component :is="filter.component" :filter="filter" :modelValue="queryParams[filter.name]"
            @update:modelValue="updateQueryParams($event)" />
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

const updateQueryParams = (value: any) => {
    if (typeof value === 'object') {
        emit('update:modelValue', value);
    } else {
        emit('update:modelValue', {
            name: props.filter.name,
            value
        });
    }
}

</script>