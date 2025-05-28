<template>
    <div class="rounded-lg border bg-card text-card-foreground shadow">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-medium">Importação de Usuários</h3>
            <ButtomImport 
                target-table="users" 
                @import-click="handleImportClick"
                variant="outline"
            />
        </div>
        <div class="p-6">
            <p class="text-sm text-muted-foreground mb-4">
                Este é um exemplo de como usar o sistema de importação de Excel. Clique no botão "Importar" para começar.
            </p>
            
            <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-md p-4 mb-6">
                <h5 class="font-medium mb-2">Instruções:</h5>
                <ul class="text-sm space-y-1 pl-5 list-disc">
                    <li>Seu arquivo Excel deve conter as seguintes colunas: name, email, password</li>
                    <li>A primeira linha deve conter os nomes das colunas</li>
                    <li>Todos os e-mails devem ser válidos</li>
                    <li>As senhas serão criptografadas automaticamente</li>
                </ul>
            </div>
            
            <div v-if="importHistory.length > 0" class="mt-6">
                <h4 class="text-sm font-medium mb-2">Histórico de Importações</h4>
                <div class="border rounded-md overflow-hidden">
                    <table class="w-full caption-bottom text-sm">
                        <thead>
                            <tr class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <th class="h-10 px-4 text-left align-middle font-medium">Data</th>
                                <th class="h-10 px-4 text-left align-middle font-medium">Arquivo</th>
                                <th class="h-10 px-4 text-left align-middle font-medium">Registros</th>
                                <th class="h-10 px-4 text-left align-middle font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in importHistory" 
                                :key="index"
                                class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted"
                            >
                                <td class="p-4 align-middle">{{ item.date }}</td>
                                <td class="p-4 align-middle">{{ item.filename }}</td>
                                <td class="p-4 align-middle">{{ item.records }}</td>
                                <td class="p-4 align-middle">
                                    <Badge :variant="getStatusVariant(item.status)">
                                        {{ item.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Referência para o componente de importação -->
        <ImportExcel 
            ref="importModal"
            target-table="users"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ButtomImport from './ButtomImport.vue';
import ImportExcel from './ImportExcel.vue';
import { Badge } from '@/components/ui/badge';

// Estado do componente
const importModal = ref(null);
const importHistory = ref([
    {
        date: '2023-10-15 14:30',
        filename: 'usuarios_exemplo.xlsx',
        records: 150,
        status: 'Concluído'
    },
    {
        date: '2023-09-22 09:15',
        filename: 'novos_usuarios.csv',
        records: 42,
        status: 'Concluído'
    },
    {
        date: '2023-08-05 16:45',
        filename: 'importacao_teste.xlsx',
        records: 5,
        status: 'Falhou'
    }
]);

// Método chamado quando o botão de importar é clicado
function handleImportClick() {
    if (importModal.value) {
        importModal.value.open();
    }
}

// Método para determinar a variante do Badge com base no status
function getStatusVariant(status) {
    switch (status) {
        case 'Concluído':
            return 'success';
        case 'Processando':
            return 'warning';
        case 'Falhou':
            return 'destructive';
        default:
            return 'secondary';
    }
}

// Se você precisar carregar histórico real de importações da API
onMounted(() => {
    // Aqui você poderia carregar o histórico real, por exemplo:
    // axios.get('/api/import/history')
    //     .then(response => {
    //         importHistory.value = response.data;
    //     })
    //     .catch(error => {
    //         console.error('Erro ao carregar histórico:', error);
    //     });
});
</script> 