<template>
    <div class="rounded-lg border bg-card text-card-foreground shadow">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-medium">Importação Multi-Tabelas</h3>
            <ButtomImport 
                :target-table="availableTables" 
                @import-click="handleImportClick"
                variant="outline"
                button-text="Importar Dados"
            />
        </div>
        <div class="p-6">
            <p class="text-sm text-muted-foreground mb-4">
                Este exemplo demonstra como usar o sistema de importação com múltiplas tabelas disponíveis.
                Clique no botão "Importar Dados" para selecionar a tabela de destino.
            </p>
            
            <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-md p-4 mb-6">
                <h5 class="font-medium mb-2">Tabelas Disponíveis:</h5>
                <ul class="text-sm space-y-1 pl-5 list-disc">
                    <li v-for="table in availableTables" :key="table.name">
                        <span class="font-medium">{{ table.label }}</span>
                        <p v-if="table.description" class="text-xs ml-2">{{ table.description }}</p>
                    </li>
                </ul>
            </div>
            
            <div v-if="lastImport" class="mt-6 p-4 rounded-md border" :class="lastImportClass">
                <h4 class="text-sm font-medium mb-2">Última Importação:</h4>
                <div class="text-sm">
                    <p><strong>Tabela:</strong> {{ getTableLabel(lastImport.table) }}</p>
                    <p><strong>Status:</strong> {{ lastImport.status === 'success' ? 'Sucesso' : 'Erro' }}</p>
                    <p v-if="lastImport.message"><strong>Mensagem:</strong> {{ lastImport.message }}</p>
                    <p v-if="lastImport.jobId"><strong>ID do Job:</strong> {{ lastImport.jobId }}</p>
                    <p><strong>Data:</strong> {{ lastImport.date }}</p>
                </div>
            </div>
        </div>
        
        <!-- Referência para o componente de importação -->
        <ImportExcel 
            ref="importModal"
            :target-table="availableTables"
            @import-complete="handleImportComplete"
        />
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import ButtomImport from './ButtomImport.vue';
import ImportExcel from './ImportExcel.vue';

// Estado do componente
const importModal = ref(null);
const lastImport = ref(null);

// Lista de tabelas disponíveis para importação
const availableTables = reactive([
    {
        name: 'users',
        label: 'Usuários',
        description: 'Importar lista de usuários (nome, email, senha)'
    },
    {
        name: 'products',
        label: 'Produtos',
        description: 'Importar catálogo de produtos (nome, descrição, preço, estoque)'
    },
    {
        name: 'customers',
        label: 'Clientes',
        description: 'Importar base de clientes (nome, telefone, endereço)'
    },
    {
        name: 'orders',
        label: 'Pedidos',
        description: 'Importar pedidos (cliente, data, valor total)'
    }
]);

// Método chamado quando o botão de importar é clicado
function handleImportClick() {
    if (importModal.value) {
        importModal.value.open();
    }
}

// Método chamado quando a importação é concluída
function handleImportComplete(result) {
    lastImport = ref({
        ...result,
        date: new Date().toLocaleString()
    });
}

// Método para obter o rótulo de exibição da tabela
function getTableLabel(tableName) {
    const table = availableTables.find(t => t.name === tableName);
    return table ? table.label : tableName;
}

// Classe CSS para o último resultado de importação
const lastImportClass = computed(() => {
    if (!lastImport.value) return '';
    
    if (lastImport.value.status === 'success') {
        return 'bg-green-50 border-green-200';
    } else {
        return 'bg-red-50 border-red-200';
    }
});
</script> 