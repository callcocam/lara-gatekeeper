<template>
    <div>
        <Table>
            <TableCaption>A list of your recent invoices.</TableCaption>
            <TableHeader>
                <TableRow>
                    <TableHead v-for="column in columns" :key="column.id">{{ column.label }}</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="item in items" :key="item.id">
                    <TableCell v-for="column in columns" :key="column.id">
                        <template v-if="column.id === 'actions' && item.actions">
                            <TableCell v-for="action in item.actions" :key="action.id">
                                <ActionRenderer :action="action" />
                            </TableCell>
                        </template>
                        <template v-else>
                            <span v-html="item[column.accessorKey]"></span>
                        </template>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
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

const props = defineProps({
    columns: {
        type: Array as () => Array<{
            id: string;
            label: string;
            accessorKey: string;
            isAction: boolean;
        }>,
        required: true
    },
    items: {
        type: Array as () => Array<Record<string, any>>,
        required: true
    }
});
</script>