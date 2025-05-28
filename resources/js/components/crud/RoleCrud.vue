<script setup lang="ts">
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import DynamicForm from '../DynamicForm.vue'
import ServerSideDataTable from '../table/ServerSideDataTable.vue'
import ConfirmationModal from '../ConfirmationModal.vue'

// Declarar route como global (assume Ziggy configurado)
declare const route: any;

interface Role {
    id: number;
    name: string;
    slug: string;
    description?: string;
    status: string;
    permissions?: Permission[];
    created_at: string;
    updated_at: string;
}

interface Permission {
    id: number;
    name: string;
    slug: string;
}

interface RoleCrudProps {
    // Dados da tabela
    roles?: Role[];
    permissions?: Permission[];
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
    // Erros de formulário
    errors?: Record<string, string[]>;
}

const props = withDefaults(defineProps<RoleCrudProps>(), {
    roles: () => [],
    permissions: () => [],
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
    baseRoute: 'admin.roles',
    currentRoute: 'admin.roles.index',
    errors: () => ({})
});

// Estado do componente
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const currentRole = ref<Role | null>(null);
const isLoading = ref(false);

// Configuração dos campos do formulário
const roleFields = computed(() => [
    {
        name: 'name',
        label: 'Nome do Papel',
        type: 'text',
        required: true,
        colSpan: 6,
        row: 0
    },
    {
        name: 'slug',
        label: 'Slug',
        type: 'text',
        required: true,
        colSpan: 6,
        row: 0
    },
    {
        name: 'description',
        label: 'Descrição',
        type: 'textarea',
        colSpan: 12,
        row: 1
    },
    {
        name: 'permissions',
        label: 'Permissões',
        type: 'checkboxList',
        options: props.permissions.reduce((acc, permission) => {
            acc[permission.id] = permission.name;
            return acc;
        }, {} as Record<string, string>),
        colSpan: 12,
        row: 2
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
        row: 3
    }
]);

// Configuração das colunas da tabela
const tableColumns = computed(() => [
    {
        id: 'name',
        header: 'Nome',
        accessorKey: 'name',
        sortable: true
    },
    {
        id: 'slug',
        header: 'Slug',
        accessorKey: 'slug',
        sortable: true
    },
    {
        id: 'description',
        header: 'Descrição',
        accessorKey: 'description'
    },
    {
        id: 'created_at',
        header: 'Criado em',
        accessorKey: 'created_at',
        sortable: true,
        formatter: 'formatDate',
        formatterOptions: 'dd/MM/yyyy HH:mm'
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
        cell: (row: Role) => `
            <div class="flex space-x-2">
                <button 
                    onclick="window.editRole(${row.id})" 
                    class="text-blue-600 hover:text-blue-900"
                    title="Editar"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                <button 
                    onclick="window.deleteRole(${row.id})" 
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
        column: 'status',
        label: 'Status',
        title: 'Status',
        type: 'select',
        options: [
            { label: 'Ativo', value: 'active' },
            { label: 'Inativo', value: 'inactive' }
        ]
    }
]);

// Valores iniciais do formulário
const initialValues = computed(() => {
    if (currentRole.value) {
        return {
            ...currentRole.value,
            permissions: currentRole.value.permissions?.map(p => p.id) || []
        };
    }
    return {
        name: '',
        slug: '',
        description: '',
        permissions: [],
        status: 'active'
    };
});

// Métodos
const openCreateModal = () => {
    currentRole.value = null;
    showCreateModal.value = true;
};

const openEditModal = (role: Role) => {
    currentRole.value = role;
    showEditModal.value = true;
};

const openDeleteModal = (role: Role) => {
    currentRole.value = role;
    showDeleteModal.value = true;
};

const closeModals = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    showDeleteModal.value = false;
    currentRole.value = null;
};

const handleSubmit = (formData: any) => {
    isLoading.value = true;
    
    const url = currentRole.value 
        ? route(`${props.baseRoute}.update`, currentRole.value.id)
        : route(`${props.baseRoute}.store`);
    
    const method = currentRole.value ? 'put' : 'post';
    
    router[method](url, formData, {
        onSuccess: () => {
            closeModals();
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('Erro ao salvar papel:', errors);
        }
    });
};

const handleDelete = () => {
    if (!currentRole.value) return;
    
    isLoading.value = true;
    
    router.delete(route(`${props.baseRoute}.destroy`, currentRole.value.id), {
        onSuccess: () => {
            closeModals();
            isLoading.value = false;
        },
        onError: (errors) => {
            isLoading.value = false;
            console.error('Erro ao excluir papel:', errors);
        }
    });
};

// Expor métodos globalmente para os botões da tabela
onMounted(() => {
    (window as any).editRole = (id: number) => {
        const role = props.roles.find(r => r.id === id);
        if (role) openEditModal(role);
    };
    
    (window as any).deleteRole = (id: number) => {
        const role = props.roles.find(r => r.id === id);
        if (role) openDeleteModal(role);
    };
});
</script>

<template>
    <div class="role-crud">
        <!-- Cabeçalho -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Gerenciar Papéis</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Gerencie os papéis e suas permissões no sistema
                </p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Novo Papel
            </button>
        </div>

        <!-- Tabela de Papéis -->
        <ServerSideDataTable
            :columns="tableColumns"
            :data="roles"
            :meta="meta"
            :links="links"
            :filters="tableFilters"
            :base-route="baseRoute"
            :current-route="currentRoute"
            table-name="roles"
        >
            <template #search="{ searchQuery, handleSearchInput }">
                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <input
                            type="text"
                            :value="searchQuery"
                            @input="handleSearchInput(($event.target as HTMLInputElement)?.value || '')"
                            placeholder="Buscar papéis..."
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
                                    Novo Papel
                                </h3>
                                <DynamicForm
                                    :fields="roleFields"
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
                                    Editar Papel
                                </h3>
                                <DynamicForm
                                    :fields="roleFields"
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
            title="Excluir Papel"
            :message="`Tem certeza que deseja excluir o papel '${currentRole?.name}'?`"
            confirm-text="Excluir"
            cancel-text="Cancelar"
            :is-loading="isLoading"
            @confirm="handleDelete"
            @cancel="closeModals"
        />
    </div>
</template>

<style scoped>
.role-crud {
    @apply w-full;
}
</style> 