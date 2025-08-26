<template>
    <component :is="getComponent(column?.component ?? 'TextColumn')" :item="item" :column="column" />
</template>
<script setup lang="ts">
import { defineProps } from 'vue'; 
import { TableCellProps } from '../../types/tables';
 

const props = defineProps<TableCellProps>(); 
 
const getComponent = (component: string) => {
    return `Gt${component}`;
};
</script>