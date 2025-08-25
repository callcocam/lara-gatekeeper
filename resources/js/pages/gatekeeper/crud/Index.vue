<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'; // Ajuste o caminho se necessário
import { Head } from '@inertiajs/vue3';
import { h, computed, toRefs, watch, ref } from 'vue'; // Restaurar imports h, resolveComponent, watch
import type { BreadcrumbItem } from '@/types'; // Supondo que BreadcrumbItem está em @/types 
import type { BackendColumnDef } from '@/types/tables'; // Importar do arquivo central 

// --- Tipos --- (Manter simples ou importar de locais compartilhados)
interface LinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

interface MetaData {
    current_page: number;
    from: number | null;
    last_page: number;
    links: LinkItem[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

interface SimpleLink {
    prev: string | null;
    next: string | null;
}

interface PaginatedData<T> {
    data: T[];
    meta: MetaData;
    links: SimpleLink;
}

interface FilterOption { // Tipo básico para opções de filtro
    key: string;
    label: string;
    type: string; // 'text', 'select', 'date', etc.
    options?: { value: string | number; label: string }[];
}

interface CanPermissions { // Estrutura de exemplo para permissões
    create_resource?: boolean;
    edit_resource?: boolean;
    delete_resource?: boolean;
    import_resource?: boolean;
    [key: string]: boolean | undefined;
}

interface ImportOptions {
    import_resource?: boolean;
    import_type?: string;
    target_table?: string;
    [key: string]: boolean | string | undefined;
}

interface Props {
    data: PaginatedData<any>;
    columns: BackendColumnDef[];
    filters: Record<string, string>;
    queryParams: FilterOption;
    searchableColumns: string[];
    pageTitle: string;
    breadcrumbs: BreadcrumbItem[];
    routeNameBase: string;
    actions?: any[];
    can?: CanPermissions;
    import?: ImportOptions;
    export?: ImportOptions;
    fullWidth?: boolean;
}

const props = defineProps<Props>();
const {
    data,
    columns,
    filters,
    queryParams,
    pageTitle,
    breadcrumbs,
    routeNameBase,
    actions,
    can,
    fullWidth
} = toRefs(props);

const getClassName = () => {
    return fullWidth.value ? ['w-full'] : ['w-full', 'max-w-7xl', 'mx-auto'];
};
</script>

<template>

    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12 w-full">
            <div class="mx-auto  sm:px-6 lg:px-8" :class="getClassName()">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-4 flex justify-end gap-2">
                             
                            <!-- Botão de Criar (usa can.create_resource ou um nome mais específico se passado) -->
                            <template v-if="actions && actions.length">
                                <ActionRenderer v-for="action in actions" :key="action.id" :action="action" />
                            </template>
                        </div>
                        <GtDataTable :items="data.data" :columns="columns" :meta="data.meta"
                            :query-params="queryParams">
                            <template #toolbar>
                                <GtDataTableToolbar :filters="filters" :query-params="queryParams"
                                    :searchable-columns="searchableColumns" />
                            </template>
                            <template #pagination="{ meta }">
                                <GtPagination :meta="meta" />
                            </template>
                        </GtDataTable>
                        <pre class="whitespace-pre-wrap rounded bg-gray-100 p-4 text-sm dark:bg-gray-700">{{ JSON.stringify(actions, null,
                            2) }}
</pre>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>