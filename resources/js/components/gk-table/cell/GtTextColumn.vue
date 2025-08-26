<template>
    <span v-html="item[column.accessorKey]"></span>
</template>
<script setup lang="ts">
import { defineProps } from 'vue'; 
import { TableCellProps } from '../../../types/tables';
const props = defineProps<TableCellProps>(); 
</script>