<template>
    <div v-if="item[column.nameFormatter || column.accessorKey]">
        <img :src="item[column.nameFormatter || column.accessorKey]"
            :alt="item[column.nameFormatter || column.accessorKey]" class="w-10 h-10 rounded-full object-cover" />
    </div>
</template>
<script setup lang="ts">
import { defineProps } from 'vue';
import { TableCellProps } from '../../../types/tables';
const props = defineProps<TableCellProps>(); 
</script>