<template>
    <div class="flex items-center space-x-4 p-4">
        <h3 class="text-lg font-semibold">Exemplo de Filtros Múltiplos</h3>
        
        <!-- Filtro de Status (múltipla seleção) -->
        <DataTableFacetedFilter
            v-model="statusFilter"
            :filter="statusFilterConfig"
            @update:modelValue="onStatusChange"
        />
        
        <!-- Filtro de Categoria (múltipla seleção) -->
        <DataTableFacetedFilter
            v-model="categoryFilter"
            :filter="categoryFilterConfig"
            @update:modelValue="onCategoryChange"
        />
    </div>
    
    <!-- Debug dos valores selecionados -->
    <div class="p-4 mt-4 bg-gray-100 rounded">
        <h4 class="font-semibold mb-2">Valores Selecionados:</h4>
        <pre>{{ JSON.stringify({ statusFilter, categoryFilter }, null, 2) }}</pre>
    </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import DataTableFacetedFilter from '../filters/DataTableFacetedFilter.vue'

// Estados dos filtros - sempre arrays para seleção múltipla
const statusFilter = ref<string[] | undefined>(undefined)
const categoryFilter = ref<string[] | undefined>(undefined)

// Configuração do filtro de status
const statusFilterConfig = {
    id: 'status',
    label: 'Status',
    name: 'status',
    component: DataTableFacetedFilter,
    options: [
        {
            label: 'Ativo',
            value: 'active',
            count: 15
        },
        {
            label: 'Inativo',
            value: 'inactive',
            count: 5
        },
        {
            label: 'Pendente',
            value: 'pending',
            count: 3
        },
        {
            label: 'Arquivado',
            value: 'archived',
            count: 2
        }
    ]
}

// Configuração do filtro de categoria
const categoryFilterConfig = {
    id: 'category',
    label: 'Categoria',
    name: 'category',
    component: DataTableFacetedFilter,
    options: [
        {
            label: 'Tecnologia',
            value: 'tech',
            count: 12
        },
        {
            label: 'Marketing',
            value: 'marketing',
            count: 8
        },
        {
            label: 'Vendas',
            value: 'sales',
            count: 10
        },
        {
            label: 'Suporte',
            value: 'support',
            count: 5
        }
    ]
}

// Handlers para mudanças nos filtros
const onStatusChange = (value: string[] | undefined) => {
    console.log('Status mudou:', value)
    statusFilter.value = value
}

const onCategoryChange = (value: string[] | undefined) => {
    console.log('Categoria mudou:', value)
    categoryFilter.value = value
}
</script>
