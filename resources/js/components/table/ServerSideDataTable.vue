<script setup lang="ts">
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
    SortDirection
} from '@tanstack/vue-table'
import type { TableData } from './schema'

import { valueUpdater } from '@/components/table/utils'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    FlexRender,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ref, watch } from 'vue'
import DataTableToolbar from '@/components/table/DataTableToolbar.vue'
// @ts-ignore
import ServerSideDataTablePagination from '@/components/table/ServerSideDataTablePagination.vue'
import type { Filter } from '@/components/table/types';
import { router } from '@inertiajs/vue3'
// @ts-ignore
import { debounce } from 'lodash'

interface ServerSideDataTableProps {
    columns: ColumnDef<any>[]
    data: TableData[]
    tableName?: string
    filters: Filter[]
    meta: {
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
    };
    links: {
        prev: string | null;
        next: string | null;
    };
    baseRoute: string;
    currentRoute: string;
}

const props = defineProps<ServerSideDataTableProps>()

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const searchQuery = ref('')

const table = useVueTable({
    get data() { return props.data },
    get columns() { return props.columns },
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get columnVisibility() { return columnVisibility.value },
        get rowSelection() { return rowSelection.value },
        pagination: {
            pageIndex: props.meta.current_page - 1,
            pageSize: props.meta.per_page,
        }
    },
    manualPagination: true,
    manualSorting: true,
    pageCount: props.meta.last_page,
    enableRowSelection: true,
    onSortingChange: updaterOrValue => {
        valueUpdater(updaterOrValue, sorting);
        handleSortingChange(sorting.value);
    },
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
})

// Função para extrair os filtros em um formato para o router
const extractFilters = () => {
    const filters: Record<string, any> = {}

    // Convertendo o Map para um objeto
    for (const [key, value] of columnFilters.value.entries()) {
        if (Array.isArray(value) && value.length) {
            filters[key] = value.join(',')
        } else if (value) {
            filters[key] = value
        }
    }

    return filters
}

// Função para extrair parâmetros de ordenação
const extractSorting = (sortingState: SortingState) => {
    if (!sortingState.length) return {}

    return {
        sort_by: sortingState[0].id,
        sort_dir: sortingState[0].desc ? 'desc' : 'asc'
    }
}

// Debounce para evitar muitas requisições ao digitar na busca
const debouncedSearch = debounce((value: string) => {
    router.get(route(props.currentRoute),
        {
            search: value || undefined,
            page: 1,  // Volta para a primeira página ao buscar
            ...extractFilters(),
            ...extractSorting(sorting.value),
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters']
        }
    )
}, 300)

// Handle para mudança na ordenação
const handleSortingChange = (sortingState: SortingState) => {
    // Evitar chamadas desnecessárias
    if (sortingState.length === 0) return

    router.get(route(props.currentRoute),
        {
            ...extractFilters(),
            ...extractSorting(sortingState),
            search: searchQuery.value || undefined,
            page: 1, // Volta para a primeira página ao ordenar
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters']
        }
    )
}

// Watch para filtros de coluna para enviar ao servidor
watch(columnFilters, (newFilters) => {
    // Verifica se o tamanho dos filtros é zero
    if (Object.keys(extractFilters()).length === 0) return

    router.get(route(props.currentRoute),
        {
            ...extractFilters(),
            ...extractSorting(sorting.value),
            search: searchQuery.value || undefined,
            page: 1, // Volta para a primeira página ao filtrar
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters']
        }
    )
}, { deep: true })

// Handle para mudança na paginação
const handlePaginationChange = (page: number) => {
    router.get(route(props.currentRoute),
        {
            page,
            search: searchQuery.value || undefined,
            ...extractFilters(),
            ...extractSorting(sorting.value),
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters']
        }
    )
}

// Handle para mudança no tamanho da página
const handlePageSizeChange = (size: number) => {
    router.get(route(props.currentRoute),
        {
            per_page: size,
            page: 1, // Volta para a primeira página ao mudar o tamanho
            search: searchQuery.value || undefined,
            ...extractFilters(),
            ...extractSorting(sorting.value)
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters']
        }
    )
}

// Atualiza o searchQuery para uso no roteador
const handleSearchInput = (value: string) => {
    searchQuery.value = value
    debouncedSearch(value)
}
</script>

<template>
    <div class="space-y-4">
        <DataTableToolbar :table="table" :filters="filters" :table-name="tableName">
            <template #search="{ table }">
                <slot name="search" :table="table" :handle-search-input="handleSearchInput" />
            </template>
            <template #filters="{ table }">
                <slot name="filters" :table="table" />
            </template>
        </DataTableToolbar>
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                            :data-state="row.getIsSelected() && 'selected'">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <TableRow v-else>
                        <TableCell :colspan="columns.length" class="h-24 text-center">
                            Nenhum resultado encontrado.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <ServerSideDataTablePagination :page-index="meta.current_page" :page-size="meta.per_page"
            :page-count="meta.last_page" :total-items="meta.total" :has-next-page="!!links.next"
            :has-previous-page="!!links.prev" @page-change="handlePaginationChange"
            @page-size-change="handlePageSizeChange" />
    </div>
</template>