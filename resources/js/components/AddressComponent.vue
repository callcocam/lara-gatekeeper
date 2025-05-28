<script setup lang="ts">
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DynamicForm from './DynamicForm.vue'
import ServerSideDataTable from './table/ServerSideDataTable.vue'
import ConfirmationModal from './ConfirmationModal.vue'
import type { FieldConfig, FormValues, FormErrors } from './DynamicForm.vue'

// Declarar route como global (assume Ziggy configurado)
declare const route: any;

interface Address {
    id: number;
    street: string;
    number: string;
    complement?: string;
    neighborhood: string;
    city: string;
    state: string;
    zip_code: string;
    country: string;
    type: 'residential' | 'commercial' | 'billing' | 'shipping' | 'other';
    status: string;
    created_at: string;
    updated_at: string;
}

interface AddressComponentProps {
    // Dados da tabela
    addresses?: Address[];
    meta?: {
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
    };
    links?: {
        prev: string | null;
        next: string | null;
    };
    // Configurações
    baseRoute?: string;
    currentRoute?: string;
    // Modo de operação
    mode?: 'standalone' | 'embedded'; // standalone = página completa, embedded = componente integrado
    // Para modo embedded
    parentModel?: string; // ex: 'user', 'client'
    parentId?: number;
    // Configurações de exibição
    showCreateButton?: boolean;
    showFilters?: boolean;
    showSearch?: boolean;
    // Erros de formulário
    errors?: FormErrors;
}

const props = withDefaults(defineProps<AddressComponentProps>(), {
    addresses: () => [],
    meta: () => ({
        current_page: 1,
        last_page: 1,
        from: 1,
        to: 0,
        total: 0,
        per_page: 10
    }),
    links: () => ({
        prev: null,
        next: null
    }),
    baseRoute: 'admin.addresses',
    currentRoute: 'admin.addresses.index',
    mode: 'standalone',
    showCreateButton: true,
    showFilters: true,
    showSearch: true,
    errors: () => ({})
});

const emit = defineEmits<{
    (e: 'addressCreated', address: Address): void;
    (e: 'addressUpdated', address: Address): void;
    (e: 'addressDeleted', addressId: number): void;
}>();

// Estado do componente
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const currentAddress = ref<Address | null>(null);
const isLoading = ref(false);

// Configuração dos campos do formulário
const addressFields = computed(() => [
    {
        name: 'street',
        label: 'Logradouro',
        type: 'text',
        required: true,
        colSpan: 8,
        row: 0
    },
    {
        name: 'number',
        label: 'Número',
        type: 'text',
        required: true,
        colSpan: 4,
        row: 0
    },
    {
        name: 'complement',
        label: 'Complemento',
        type: 'text',
        colSpan: 6,
        row: 1
    },
    {
        name: 'neighborhood',
        label: 'Bairro',
        type: 'text',
        required: true,
        colSpan: 6,
        row: 1
    },
    {
        name: 'city',
        label: 'Cidade',
        type: 'text',
        required: true,
        colSpan: 6,
        row: 2
    },
    {
        name: 'state',
        label: 'Estado',
        type: 'text',
        required: true,
        colSpan: 3,
        row: 2
    },
    {
        name: 'zip_code',
        label: 'CEP',
        type: 'text',
        required: true,
        colSpan: 3,
        row: 2
    },
    {
        name: 'country',
        label: 'País',
        type: 'text',
        required: true,
        colSpan: 6,
        row: 3
    },
    {
        name: 'type',
        label: 'Tipo',
        type: 'select',
        required: true,
        options: {
            'residential': 'Residencial',
            'commercial': 'Comercial',
            'billing': 'Cobrança',
            'shipping': 'Entrega',
            'other': 'Outro'
        },
        colSpan: 6,
        row: 3
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: {
            'active': 'Ativo',
            'inactive': 'Inativo'
        },
        colSpan: 12,
        row: 4
    }
]);

// Configuração das colunas da tabela
const tableColumns = computed(() => [
    {
        id: 'street',
        header: 'Logradouro',
        accessorKey: 'street',
        sortable: true
    },
    {
        id: 'number',
        header: 'Número',
        accessorKey: 'number',
        sortable: true
    },
    {
        id: 'neighborhood',
        header: 'Bairro',
        accessorKey: 'neighborhood',
        sortable: true
    },
    {
        id: 'city',
        header: 'Cidade',
        accessorKey: 'city',
        sortable: true
    },
    {
        id: 'state',
        header: 'Estado',
        accessorKey: 'state',
        sortable: true
    },
    {
        id: 'zip_code',
        header: 'CEP',
        accessorKey: 'zip_code',
        sortable: true
    },
    {
        id: 'type',
        header: 'Tipo',
        accessorKey: 'type',
        sortable: true,
        formatter: 'renderBadge',
        formatterOptions: {
            'residential': { label: 'Residencial', variant: 'default' },
            'commercial': { label: 'Comercial', variant: 'secondary' },
            'billing': { label: 'Cobrança', variant: 'outline' },
            'shipping': { label: 'Entrega', variant: 'destructive' },
            'other': { label: 'Outro', variant: 'secondary' }
        }
    },
    {
        id: 'status',
        header: 'Status',
        accessorKey: 'status',
        sortable: true,
        formatter: 'renderBadge',
        formatterOptions: {
            'active': { label: 'Ativo', variant: 'default' },
            'inactive': { label: 'Inativo', variant: 'secondary' }
        }
    },
    {
        id: 'actions',
        header: 'Ações',
        accessorKey: undefined,
        html: true,
        cell: (row: Address) => `
            <div class="flex space-x-2">
                <button 
                    onclick="window.editAddress(${row.id})" 
                    class="text-blue-600 hover:text-blue-900"
                    title="Editar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                <button 
                    onclick="window.deleteAddress(${row.id})" 
                    class="text-red-600 hover:text-red-900"
                    title="Excluir"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `
    }
]);

// Filtros da tabela
const tableFilters = computed(() => [
    {
        column: 'type',
        label: 'Tipo',
        title: 'Tipo',
        type: 'select',
        options: {
            'residential': 'Residencial',
            'commercial': 'Comercial',
            'billing': 'Cobrança',
            'shipping': 'Entrega',
            'other': 'Outro'
        }
    },
    {
        column: 'status',
        label: 'Status',
        title: 'Status',
        type: 'select',
        options: {
            'active': 'Ativo',
            'inactive': 'Inativo'
        }
    }
]);

// Valores iniciais do formulário
const initialValues = computed<FormValues>(() => {
    if (currentAddress.value) {
        return { ...currentAddress.value };
    }
    return {
        street: '',
        number: '',
        complement: '',
        neighborhood: '',
        city: '',
        state: '',
        zip_code: '',
        country: 'Brasil',
        type: 'residential',
        status: 'active'
    };
});

// Métodos
const openCreateModal = () => {
    currentAddress.value = null;
    showCreateModal.value = true;
};

const openEditModal = (address: Address) => {
    currentAddress.value = address;
    showEditModal.value = true;
};

const openDeleteModal = (address: Address) => {
    currentAddress.value = address;
    showDeleteModal.value = true;
};

const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    showDeleteModal.value = false;
    currentAddress.value = null;
};

const handleSubmit = (formData: FormValues) => {
    isLoading.value = true;
    
    const url = currentAddress.value 
        ? route(`${props.baseRoute}.update`, currentAddress.value.id)
        : route(`${props.baseRoute}.store`);
    
    const method = currentAddress.value ? 'put' : 'post';
    
    // Adicionar dados do modelo pai se em modo embedded
    if (props.mode === 'embedded' && props.parentModel && props.parentId) {
        formData[`${props.parentModel}_id`] = props.parentId;
    }
    
    router[method](url, formData, {
        onSuccess: (page) => {
            closeModals();
            isLoading.value = false;
            
            // Emitir eventos para o componente pai
            if (currentAddress.value) {
                emit('addressUpdated', page.props.address as Address);
            } else {
                emit('addressCreated', page.props.address as Address);
            }
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('Erro ao salvar endereço:', errors);
        }
    });
};

const handleDelete = () => {
    if (!currentAddress.value) return;
    
    isLoading.value = true;
    
    router.delete(route(`${props.baseRoute}.destroy`, currentAddress.value.id), {
        onSuccess: () => {
            closeModals();
            isLoading.value = false;
            emit('addressDeleted', currentAddress.value!.id);
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('Erro ao excluir endereço:', errors);
        }
    });
};

// Expor métodos globalmente para os botões da tabela
onMounted(() => {
    (window as any).editAddress = (id: number) => {
        const address = props.addresses.find(a => a.id === id);
        if (address) openEditModal(address);
    };
    
    (window as any).deleteAddress = (id: number) => {
        const address = props.addresses.find(a => a.id === id);
        if (address) openDeleteModal(address);
    };
});
</script>

<template>
    <div class="address-component">
        <!-- Cabeçalho -->
        <div class="flex justify-between items-center mb-6" v-if="mode === 'standalone'">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Gerenciar Endereços</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie os endereços do sistema
                </p>
            </div>
            <button
                v-if="showCreateButton"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Novo Endereço
            </button>
        </div>

        <!-- Botão para modo embedded -->
        <div class="flex justify-end mb-4" v-if="mode === 'embedded' && showCreateButton">
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Adicionar Endereço
            </button>
        </div>

        <!-- Tabela de Endereços -->
        <ServerSideDataTable
            :columns="tableColumns"
            :data="addresses"
            :meta="meta"
            :links="links"
            :filters="showFilters ? tableFilters : []"
            :base-route="baseRoute"
            :current-route="currentRoute"
            table-name="addresses"
        >
            <template #search="{ searchQuery, handleSearchInput }" v-if="showSearch">
                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <input
                            type="text"
                            :value="searchQuery"
                            @input="handleSearchInput(($event.target as HTMLInputElement)?.value || '')"
                            placeholder="Buscar endereços..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>
        </ServerSideDataTable>

        <!-- Modal de Criação -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModals"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Novo Endereço
                                </h3>
                                <DynamicForm
                                    :fields="addressFields"
                                    :initial-values="initialValues"
                                    :errors="errors"
                                    @submit="handleSubmit"
                                >
                                    <template #actions="{ submit }">
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button
                                                type="button"
                                                @click="submit"
                                                :disabled="isLoading"
                                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                            >
                                                <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ isLoading ? 'Salvando...' : 'Salvar' }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="closeModals"
                                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </template>
                                </DynamicForm>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Edição -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModals"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Editar Endereço
                                </h3>
                                <DynamicForm
                                    :fields="addressFields"
                                    :initial-values="initialValues"
                                    :errors="errors"
                                    @submit="handleSubmit"
                                >
                                    <template #actions="{ submit }">
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button
                                                type="button"
                                                @click="submit"
                                                :disabled="isLoading"
                                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                                            >
                                                <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ isLoading ? 'Salvando...' : 'Atualizar' }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="closeModals"
                                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </template>
                                </DynamicForm>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <ConfirmationModal
            :show="showDeleteModal"
            title="Excluir Endereço"
            :message="`Tem certeza que deseja excluir o endereço '${currentAddress?.street}, ${currentAddress?.number}'?`"
            confirm-text="Excluir"
            cancel-text="Cancelar"
            :is-loading="isLoading"
            @confirm="handleDelete"
            @cancel="closeModals"
        />
    </div>
</template>

<style scoped>
.address-component {
    @apply w-full;
}
</style> 