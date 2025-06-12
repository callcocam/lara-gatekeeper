<script setup lang="ts">
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table' 
import type { Filter } from './types';

import { valueUpdater } from './utils'
import {
    FlexRender,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ref, watch, computed, h, resolveComponent, inject } from 'vue'
import DataTableToolbar from './DataTableToolbar.vue'
import ServerSideDataTablePagination from './ServerSideDataTablePagination.vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { formatterRegistryKey } from '../../injectionKeys' 
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table' 

// Declara route como global (assume Ziggy configurado na app principal)
declare const route: any;

interface BackendColumnDef {
    accessorKey?: string;
    id?: string;
    header?: any;
    cell?: any; // Pode ser definido no backend ou frontend
    sortable?: boolean;
    enableHiding?: boolean;
    // Outras props que o backend possa enviar (ex: class, meta)
    [key: string]: any;
}

interface ServerSideDataTableProps {
    columns: BackendColumnDef[];
    data: any[];
    tableName?: string;
    filters: Filter[];
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
    columnFiltersProp?: Record<string, string>;
}

const props = defineProps<ServerSideDataTableProps>()

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const internalSearchQuery = ref('')

// Injetar o registro de formatadores AQUI
const formatterRegistry = inject(formatterRegistryKey);

const initializeStateFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialFilters: ColumnFiltersState = [];
    props.filters.forEach(filterConfig => {
        const paramValue = urlParams.get(filterConfig.column);
        if (paramValue) {
            const filterType = props.filters.find(f => f.column === filterConfig.column);
            if (filterType?.options) {
                initialFilters.push({ id: filterConfig.column, value: paramValue.split(',') });
            } else {
                initialFilters.push({ id: filterConfig.column, value: paramValue });
            }
        }
    });
    columnFilters.value = initialFilters;

    const sortBy = urlParams.get('sort');
    const sortDir = urlParams.get('direction');
    if (sortBy) {
        sorting.value = [{ id: sortBy, desc: sortDir === 'desc' }];
    }

    const search = urlParams.get('search');
    internalSearchQuery.value = search || '';
 
};

initializeStateFromUrl();

// DEBUG: Watch column visibility changes
watch(columnVisibility, (newState) => {
    // console.log('[Gatekeeper/Table] Column Visibility Changed:', newState);
}, { deep: true });

// Mapear colunas de props (BackendColumnDef) para ColumnDef do Tanstack Table
const tableColumns = computed(() => {
    const backendColumns = Array.isArray(props.columns) ? props.columns : [];
    // DEBUG: Logar as colunas recebidas antes do mapeamento
    // console.log('[Gatekeeper/Table] Received backendColumns before map:', JSON.parse(JSON.stringify(backendColumns)));

    return backendColumns.map((backendCol: BackendColumnDef): ColumnDef<any> => {
        const columnKey = backendCol.accessorKey || backendCol.id;
        // console.log(`[Gatekeeper/Table] Mapping column ID: ${backendCol.id}, Formatter value:`, backendCol.formatter);

        // DEBUG: Logar o objeto completo SEM stringify/parse
        if (backendCol.id === 'created_at' || backendCol.id === 'status') {
            // console.log(`[Gatekeeper/Table] Direct backendCol for ID ${backendCol.id} before cell:`, backendCol);
        }

        // Determinar a função de callback da célula, PRIORIZANDO o formatter
        let cellCallback: ColumnDef<any>['cell'] = undefined;
        if (backendCol.formatter) { // PRIORIDADE 1: Formatador
            cellCallback = (info: any) => { 
                if (!formatterRegistry) { 
                    const val = info.getValue();
                    return val === null || val === undefined ? '' : String(val);
                }
                const formatterName = info.column.columnDef.meta?.formatter as string | undefined;
                const formatterOptions = info.column.columnDef.meta?.formatterOptions;
                const value = info.getValue();
                if (formatterName && formatterRegistry[formatterName]) {
                    try {
                        return formatterRegistry[formatterName](value, formatterOptions, info);
                    } catch (e) { 
                        return value === null || value === undefined ? '' : String(value);
                    }
                } else {
                    if (formatterName) {
                         console.warn(`[LaraGatekeeper] Column formatter "${formatterName}" not found in registry.`);
                    }
                    return value === null || value === undefined ? '' : String(value);
                }
            }
        } else if (backendCol.cell) { // PRIORIDADE 2: Célula pré-definida (do composable)
            cellCallback = backendCol.cell; 
        } else { // PRIORIDADE 3: Fallback (valor bruto ou vazio)
            cellCallback = (info: any) => { 
                const value = info.getValue();
                // Renderizar valor bruto se houver chave, senão vazio
                return columnKey ? h('span', value === null || value === undefined ? '' : String(value)) : ''; 
            }
        }

        const tanstackCol: ColumnDef<any> = {
            id: backendCol.id ?? columnKey ?? Math.random().toString(36).substring(7),
            accessorKey: backendCol.accessorKey,
            header: ({ column }) => {
                if (backendCol.id === 'actions' || backendCol.header?.type === 'html' || backendCol.html) {
                    return h('div', { innerHTML: backendCol.header?.content || backendCol.header });
                } else {
                    return h(resolveComponent('DataTableColumnHeader'), {
                        column: column,
                        title: backendCol.header as string,
                    });
                }
            },
            cell: cellCallback, // Usar o callback determinado
            enableSorting: backendCol.enableSorting ?? true,
            enableHiding: backendCol.enableHiding ?? true,
            meta: {
                ...(backendCol.meta ?? {}),
                formatter: backendCol.formatter,
                formatterOptions: backendCol.formatterOptions,
                html: backendCol.html ?? false,
            },
            ...(backendCol.size && { size: backendCol.size }),
            ...(backendCol.minSize && { minSize: backendCol.minSize }),
            ...(backendCol.maxSize && { maxSize: backendCol.maxSize }),
        };

        return tanstackCol;
    });
});

const table = useVueTable({
    get data() { return props.data },
    get columns() { return tableColumns.value },
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
    manualFiltering: true,
    pageCount: props.meta.last_page,
    enableRowSelection: true,
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updater => {
        if (typeof updater === 'function') {
            columnVisibility.value = updater(columnVisibility.value)
        } else {
            columnVisibility.value = updater
        }
    },
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
})

const navigate = (preservePage: boolean = false) => {
    console.log('[Gatekeeper/Table] Navigating with state:', {
        filters: extractFiltersForUrl(),
        sorting: extractSortingForUrl(),
        search: internalSearchQuery.value
    });

    router.get(route(props.currentRoute).toString(),
        {
            ...(preservePage ? { page: props.meta.current_page } : { page: 1 }),
            ...extractFiltersForUrl(),
            ...extractSortingForUrl(),
            search: internalSearchQuery.value || undefined,
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const debouncedNavigate = debounce(() => navigate(false), 400);
const debouncedNavigatePreservePage = debounce(() => navigate(true), 400);

const extractFiltersForUrl = (): Record<string, string | undefined> => {
    const urlFilters: Record<string, string | undefined> = {};
    columnFilters.value.forEach(filter => {
        if (Array.isArray(filter.value) && filter.value.length > 0) {
            urlFilters[filter.id] = filter.value.join(',');
        } else if (!Array.isArray(filter.value) && filter.value) {
            urlFilters[filter.id] = String(filter.value);
        } else {
            urlFilters[filter.id] = undefined;
        }
    });
    return urlFilters;
}

const extractSortingForUrl = (): Record<string, string | undefined> => {
    if (!sorting.value.length) return { sort: undefined, direction: undefined };
    return {
        sort: sorting.value[0].id,
        direction: sorting.value[0].desc ? 'desc' : 'asc'
    }
}

watch(sorting, () => {
    navigate(false);
}, { deep: true });

watch(columnFilters, () => {
    debouncedNavigate();
}, { deep: true });

const updateInternalSearch = (value: string) => {
    internalSearchQuery.value = value;
    debouncedNavigate();
}

const handlePageChange = (newPage: number) => {
    router.get(route(props.currentRoute).toString(),
        {
            page: newPage,
            ...extractFiltersForUrl(),
            ...extractSortingForUrl(),
            search: internalSearchQuery.value || undefined,
            per_page: props.meta.per_page
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const handlePageSizeChange = (newSize: number) => {
    console.log(`[Gatekeeper/Table] Page size changed to: ${newSize}`);
    table.setPageSize(newSize);
    router.get(route(props.currentRoute).toString(),
        {
            per_page: newSize,
            page: 1,
            ...extractFiltersForUrl(),
            ...extractSortingForUrl(),
            search: internalSearchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}
</script>

<template>
    <div class="space-y-4">
        <DataTableToolbar :table="table" :filters="props.filters" :table-name="props.tableName">
            <template #search="{ table }">
                <slot name="search" :table="table" :searchQuery="internalSearchQuery"
                    :handleSearchInput="updateInternalSearch" />
            </template>
            <template #filters="{ table }">
                <slot name="filters" :table="table" />
            </template>
        </DataTableToolbar>
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id" :colspan="header.colSpan">
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
                    <template v-else>
                        <TableRow>
                            <TableCell :colSpan="columns.length" class="h-24 text-center">
                                Nenhum resultado encontrado.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        <ServerSideDataTablePagination :page-index="props.meta.current_page" :page-size="props.meta.per_page"
            :page-count="props.meta.last_page" :total-items="props.meta.total" :has-previous-page="!!props.links.prev"
            :has-next-page="!!props.links.next" @page-change="handlePageChange"
            @page-size-change="handlePageSizeChange" />
    </div>
</template>