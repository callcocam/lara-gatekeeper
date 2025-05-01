<script setup lang="ts">
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table'
import type { TableData } from './schema'
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
import { ref, watch } from 'vue'
import DataTableToolbar from './DataTableToolbar.vue'
import ServerSideDataTablePagination from './ServerSideDataTablePagination.vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

// Declara route como global (assume Ziggy configurado na app principal)
declare const route: any;

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
const internalSearchQuery = ref('')

const initializeStateFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialFilters: ColumnFiltersState = [];
    props.filters.forEach(filterConfig => {
        const paramValue = urlParams.get(filterConfig.column);
        if (paramValue) {
            const filterType = props.filters.find(f => f.column === filterConfig.column);
            if(filterType?.options) {
                initialFilters.push({ id: filterConfig.column, value: paramValue.split(',') });
            } else {
                initialFilters.push({ id: filterConfig.column, value: paramValue });
            }
        }
    });
    columnFilters.value = initialFilters;

    const sortBy = urlParams.get('sort_by');
    const sortDir = urlParams.get('sort_dir');
    if (sortBy) {
        sorting.value = [{ id: sortBy, desc: sortDir === 'desc' }];
    }

    const search = urlParams.get('search');
    internalSearchQuery.value = search || '';

    console.log('[Gatekeeper/Table] Initial state from URL:', {
        filters: columnFilters.value,
        sorting: sorting.value,
        search: internalSearchQuery.value
    });
};

initializeStateFromUrl();

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
    manualFiltering: true,
    pageCount: props.meta.last_page,
    enableRowSelection: true,
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
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
    if (!sorting.value.length) return { sort_by: undefined, sort_dir: undefined };
    return {
        sort_by: sorting.value[0].id,
        sort_dir: sorting.value[0].desc ? 'desc' : 'asc'
    }
}

watch(sorting, () => {
    console.log('[Gatekeeper/Table] Sorting changed');
    navigate(false);
}, { deep: true });

watch(columnFilters, () => {
    console.log('[Gatekeeper/Table] Filters changed');
    debouncedNavigate();
}, { deep: true });

const updateInternalSearch = (value: string) => {
    internalSearchQuery.value = value;
    debouncedNavigate();
}

const handlePageChange = (newPage: number) => {
    console.log(`[Gatekeeper/Table] Page changed to: ${newPage}`);
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
                <slot name="search" 
                    :table="table" 
                    :searchQuery="internalSearchQuery" 
                    :handleSearchInput="updateInternalSearch"
                />
            </template>
            <template #filters="{ table }">
                <slot name="filters" :table="table" />
            </template>
        </DataTableToolbar>
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id" :col-span="header.colSpan">
                             <FlexRender v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="row.getIsSelected() && 'selected'"
                        >
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
        <ServerSideDataTablePagination
             :page-index="props.meta.current_page"
             :page-size="props.meta.per_page"
             :page-count="props.meta.last_page"
             :total-items="props.meta.total"
             :has-previous-page="!!props.links.prev"
             :has-next-page="!!props.links.next"
             @page-change="handlePageChange"
             @page-size-change="handlePageSizeChange"
        />
    </div>
</template>