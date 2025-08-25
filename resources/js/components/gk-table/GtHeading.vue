<template>
    <TableHead @click="handleSort" class="cursor-pointer select-none">
        <div class="flex items-center justify-between">
            <span>{{ column.label }}</span>
            <ChevronUp class="h-4 w-4" v-if="getSortIcon() === 'asc'" />
            <ChevronDown class="h-4 w-4" v-if="getSortIcon() === 'desc'" />
            <ChevronsUpDown class="h-4 w-4" v-if="getSortIcon() === 'none'" />
        </div>
    </TableHead>
</template>
<script setup lang="ts">
import { TableHead } from '@/components/ui/table';
import { ChevronDown, ChevronsUpDown, ChevronUp } from 'lucide-vue-next';
import { defineProps, computed, toRefs } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    queryParams: {
        type: Object as () => {
            [key: string]: any;
        },
        required: true
    },
    column: {
        type: Object as () => {
            id: string;
            label: string;
            accessorKey: string;
            sortable: boolean;
        },
        required: true
    }
});

const { queryParams, column } = toRefs(props);

// Computed para determinar se esta coluna está sendo ordenada
const isCurrentSortColumn = computed(() => {
    return queryParams.value?.sort === column.value.accessorKey;
});

// Computed para a direção atual
const currentDirection = computed(() => {
    if (!isCurrentSortColumn.value) return null;
    return queryParams.value?.direction || null;
});

// Função para determinar qual ícone mostrar
const getSortIcon = () => {
    if (!isCurrentSortColumn.value) {
        return 'none'; // Mostra ChevronsUpDown
    }
    
    if (currentDirection.value === 'asc') {
        return 'asc'; // Mostra ChevronUp
    } else if (currentDirection.value === 'desc') {
        return 'desc'; // Mostra ChevronDown
    }
    
    return 'none';
};

const handleSort = () => {
    const url = new URL(window.location.href, window.location.origin);

    // Determinar próxima direção
    let nextDirection;
    if (!isCurrentSortColumn.value || !currentDirection.value) {
        // Se não está ordenando por esta coluna ou sem direção, começa com asc
        nextDirection = 'asc';
    } else if (currentDirection.value === 'asc') {
        // Se está asc, vai para desc
        nextDirection = 'desc';
    } else {
        // Se está desc, remove ordenação
        nextDirection = null;
    }

    // Atualizar queryParams
    if (nextDirection) {
        queryParams.value.sort = column.value.accessorKey;
        queryParams.value.direction = nextDirection;
    } else {
        // Remover ordenação
        delete queryParams.value.sort;
        delete queryParams.value.direction;
    }

    // Construir URL - primeiro limpar todos os parâmetros antigos
    url.search = '';    
    // Adicionar todos os parâmetros atuais
    Object.entries(queryParams.value).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            // Se é array, converte para string separada por vírgulas
            if (Array.isArray(value)) {
                if (value.length > 0) {
                    url.searchParams.set(key, value.join(','));
                }
            } else {
                url.searchParams.set(key, String(value));
            }
        }
    });
    router.visit(url.toString(), { preserveState: true, replace: true });
};

</script>