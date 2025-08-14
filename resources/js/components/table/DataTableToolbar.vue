<script setup lang="ts">
import type { Table } from '@tanstack/vue-table' 
    import { computed } from 'vue'
import { X } from 'lucide-vue-next'
import DataTableFacetedFilter from './DataTableFacetedFilter.vue'
import DataTableViewOptions from './DataTableViewOptions.vue'
import type { Filter } from './types'
import { Button } from '@/components/ui/button'
interface DataTableToolbarProps {
    table: Table<any>
    filters?: Filter[]
    tableName?: string
}

const props = defineProps<DataTableToolbarProps>()

const isFiltered = computed(() => props.table.getState().columnFilters.length > 0)
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex flex-1 items-center space-x-2 flex-wrap gap-1">
            <slot name="search" :table="table" />
            <template v-if="filters && filters.length > 0">
                <div v-for="filter in filters" :key="filter.column">
                    <DataTableFacetedFilter :column="table.getColumn(filter.column)" :title="filter.title" :options="filter.options ?? []" />
                </div>
            </template>
            <slot name="filters" :table="table" />

            <Button v-if="isFiltered" variant="ghost" class="h-8 px-2 lg:px-3" @click="table.resetColumnFilters()">
                Limpar
                <X class="ml-2 h-4 w-4" />
            </Button>
        </div>
        <DataTableViewOptions :table="table" :table-name="props.tableName" />
    </div>
</template>