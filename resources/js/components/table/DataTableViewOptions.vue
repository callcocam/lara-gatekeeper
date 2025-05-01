<script setup lang="ts">
import type { Table } from '@tanstack/vue-table'
import { computed } from 'vue'
import { Settings2 } from 'lucide-vue-next'
 

interface DataTableViewOptionsProps {
    table: Table<any>
    tableName?: string
}

const props = defineProps<DataTableViewOptionsProps>()

const visibilityStorageKey = computed(() => props.tableName ? `table_visibility_${props.tableName}` : null)

const columns = computed(() => props.table.getAllColumns()
    .filter(
        column =>
            typeof column.accessorFn !== 'undefined' && column.getCanHide(),
    ))

// Lógica para carregar/salvar visibilidade (opcional)
// onMounted(() => {
//     if (visibilityStorageKey.value) {
//         const storedVisibility = localStorage.getItem(visibilityStorageKey.value);
//         if (storedVisibility) {
//             props.table.setColumnVisibility(JSON.parse(storedVisibility));
//         }
//     }
// });

// watch(props.table.getState().columnVisibility, (newVisibility) => {
//     if (visibilityStorageKey.value) {
//         localStorage.setItem(visibilityStorageKey.value, JSON.stringify(newVisibility));
//     }
// }, { deep: true });

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="ml-auto hidden h-8 lg:flex">
                <Settings2 class="mr-2 h-4 w-4" />
                Colunas
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[150px]">
            <DropdownMenuLabel>Alternar Colunas</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
                v-for="column in columns"
                :key="column.id"
                class="capitalize"
                :checked="column.getIsVisible()" 
                @update:checked="(value: boolean) => column.toggleVisibility(!!value)" 
            >
                {{ typeof column.columnDef.header === 'string' ? column.columnDef.header : column.id }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>