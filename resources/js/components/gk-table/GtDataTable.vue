<template>
    <div class="flex flex-col w-full">
        <slot name="toolbar" />
        <Table>
            <TableCaption v-if="!items.length">Nenhum registro encontrado.</TableCaption>
            <TableHeader>
                <template v-for="column in columns" :key="column.id">
                    <GtHeading v-if="column.sortable" :column="column" :query-params="queryParams" />
                    <TableHead v-else>{{ column.label }}</TableHead>
                </template>
            </TableHeader>
            <TableBody>
                <TableRow v-for="item in items" :key="item.id">
                    <TableCell v-for="column in columns" :key="column.id">
                        <template v-if="column.id === 'actions' && item.actions">
                            <div class="flex items-center space-x-1 justify-start">
                                <ActionRenderer v-for="action in item.actions" :key="action.id" :action="action" />
                            </div>
                        </template>
                        <template v-else>
                            <span v-html="item[column.accessorKey]"></span>
                        </template>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
        <slot name="pagination" :meta="meta" />
    </div>
</template>
<script setup lang="ts">

import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table' 
import { ref } from 'vue';
import GtHeading from './GtHeading.vue';

const props = defineProps({
    queryParams: {
        type: Object as () => {
            [key: string]: any;
        },
        required: true
    },
    columns: {
        type: Array as () => Array<{
            id: string;
            label: string;
            accessorKey: string;
            isAction: boolean;
            sortable: boolean;
        }>,
        required: true
    },
    items: {
        type: Array as () => Array<Record<string, any>>,
        required: true
    },
    meta: {
        type: Object as () => {
            total: number;
            per_page: number;
            current_page: number;
            last_page: number;
            from: number;
            to: number;
        },
        required: true
    }
});
</script>