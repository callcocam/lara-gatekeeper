<template>
    <Dialog :open="isOpen" @update:open="updateOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Importar Dados</DialogTitle>
                <DialogDescription>
                    Carregue um arquivo Excel (.xlsx, .xls, .csv) para importação de dados.
                </DialogDescription>
            </DialogHeader>

            <div class="py-4" v-if="!isUploading && !importStatus">
                <form @submit.prevent="handleFileSubmit">
                    <!-- Seleção de tabela (apenas se houver múltiplas tabelas disponíveis) -->
                    <div class="mb-4" v-if="!showColumnMapping && availableTables.length > 1">
                        <h3 class="text-sm font-medium mb-2">Selecione a tabela de destino</h3>
                        <div class="space-y-2 border rounded-md p-3 max-h-[150px] overflow-y-auto">
                            <RadioGroup v-model="selectedTable">
                                <div v-for="table in availableTables" :key="table.name"
                                    class="flex items-center space-x-2 py-1">
                                    <RadioGroupItem :value="table.name" :id="`table-${table.name}`" />
                                    <Label :for="`table-${table.name}`" class="cursor-pointer">
                                        <div class="font-medium">{{ table.label }}</div>
                                        <p v-if="table.description" class="text-xs text-gray-500">
                                            {{ table.description }}
                                        </p>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </div>
                    <div class="mb-4" v-if="options?.clients">
                        <h3 class="text-sm font-medium mb-2">Selecione o cliente</h3>
                        <div class="space-y-2 border rounded-md p-3 max-h-[150px] overflow-y-auto">
                            <Select v-model="options.client_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Selecione o cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="client in options.clients" :key="client.id"
                                        :value="client.id">
                                        {{ client.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="mb-4" v-if="options?.sheets">
                        <h3 class="text-sm font-medium mb-2">Selecione a aba</h3>
                        <div class="space-y-2 border rounded-md p-3 max-h-[150px] overflow-y-auto">
                            <RadioGroup v-model="options.sheet_name">
                                <div v-for="sheet in options.sheets" :key="sheet"
                                    class="flex items-center space-x-2 py-1">
                                    <RadioGroupItem :value="sheet" :id="`sheet-${sheet}`" />
                                    <Label :for="`sheet-${sheet}`" class="cursor-pointer">
                                        <div class="font-medium">{{ sheet }}</div>
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </div>

                    <!-- Seleção de arquivo -->
                    <div class="mb-4" v-if="!showColumnMapping">
                        <Label for="excelFile" class="mb-2 block">Arquivo Excel</Label>
                        <Input type="file" id="excelFile" ref="fileInput" @change="handleFileSelect"
                            accept=".xlsx,.xls,.csv" required class="cursor-pointer" />
                        <p class="mt-1 text-sm text-gray-500">
                            Selecione um arquivo Excel para importar.
                        </p>
                    </div>

                    <!-- Opções de importação -->
                    <div class="mb-4" v-if="!showColumnMapping">
                        <h3 class="text-sm font-medium mb-2">Opções de importação</h3>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Checkbox id="headerRow" v-model:checked="options.headerRow" />
                                <Label for="headerRow" class="text-sm">
                                    Primeira linha contém cabeçalho
                                </Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="skipValidation" v-model:checked="options.skipValidation" />
                                <Label for="skipValidation" class="text-sm">
                                    Pular validação (não recomendado)
                                </Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <Checkbox id="customMapping" v-model:checked="options.customMapping" />
                                <Label for="customMapping" class="text-sm">
                                    Personalizar mapeamento de colunas
                                </Label>
                            </div>
                        </div>
                    </div>

                    <!-- Mapeamento personalizado de colunas -->
                    <div v-if="showColumnMapping" class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium mb-2">
                                Mapeamento de colunas para <span class="font-bold">{{ getTableLabel(selectedTable)
                                    }}</span>
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Selecione qual coluna do Excel corresponde a cada campo da tabela.
                            </p>
                        </div>

                        <div class="max-h-[250px] overflow-y-auto space-y-2">
                            <div v-for="(field, index) in tableFields" :key="index" class="flex items-center space-x-3">
                                <div class="w-1/3">
                                    <Label :for="`field-${index}`" class="text-sm">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </Label>
                                </div>
                                <div class="w-2/3">
                                    <Select :id="`field-${index}`" v-model="columnMapping[field.name]">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione a coluna" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                <i>Pular este campo</i>
                                            </SelectItem>
                                            <SelectItem v-for="(column, colIndex) in excelColumns" :key="colIndex"
                                                :value="column.value">
                                                {{ column.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="field.description" class="text-xs text-gray-500 mt-1">{{ field.description
                                        }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="excelPreview.length > 0" class="border rounded-md mt-4">
                            <h4 class="text-sm font-medium p-2 border-b bg-muted">Prévia dos dados</h4>
                            <div class="max-h-[200px] overflow-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th class="p-2 text-left" v-for="col in excelColumns" :key="col.value">
                                                {{ col.label }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, rowIndex) in excelPreview" :key="rowIndex" class="border-b">
                                            <td class="p-2" v-for="col in excelColumns" :key="col.value">
                                                {{ row[col.value] }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <div v-if="showColumnMapping">
                            <Button variant="outline" type="button" @click="backToFileSelection">
                                Voltar
                            </Button>
                            <Button type="submit" class="ml-2">
                                Iniciar Importação
                            </Button>
                        </div>
                        <div v-else>
                            <Button variant="outline" type="button" @click="isOpen = false">
                                Cancelar
                            </Button>
                            <Button type="submit"
                                :disabled="!selectedFile || (availableTables.length > 1 && !selectedTable)"
                                class="ml-2">
                                {{ options.customMapping ? 'Próximo' : 'Iniciar Importação' }}
                            </Button>
                        </div>
                    </DialogFooter>
                </form>
            </div>

            <!-- Área de progresso -->
            <div v-if="isUploading || importStatus" class="py-4">
                <div v-if="isUploading" class="mb-4">
                    <div class="mb-2 flex justify-between">
                        <span class="text-sm">Enviando arquivo...</span>
                        <span class="text-sm">{{ uploadProgress }}%</span>
                    </div>
                    <Progress :value="uploadProgress" class="w-full" />
                </div>

                <div v-if="importStatus" class="rounded-md p-4" :class="importStatusClass">
                    <div v-if="importStatus === 'queued'" class="flex">
                        <Loader2 class="h-4 w-4 animate-spin mr-2" />
                        <p class="text-sm">Arquivo na fila de processamento. Você receberá uma notificação quando for
                            concluído.</p>
                    </div>
                    <div v-else-if="importStatus === 'success'" class="flex">
                        <CheckCircle class="h-4 w-4 mr-2" />
                        <p class="text-sm">Importação iniciada com sucesso! O processo está em andamento.</p>
                    </div>
                    <div v-else-if="importStatus === 'error'" class="flex">
                        <AlertTriangle class="h-4 w-4 mr-2" />
                        <p class="text-sm">{{ errorMessage }}</p>
                    </div>
                </div>

                <DialogFooter v-if="importStatus">
                    <Button variant="outline" type="button" @click="resetForm">
                        Fechar
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { ref, reactive, computed, defineProps, defineEmits, watch } from 'vue';
import axios from 'axios';

// Componentes Shadcn-Vue
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    RadioGroup,
    RadioGroupItem
} from '@/components/ui/radio-group';

// Ícones
import { Loader2, CheckCircle, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    // Pode ser uma única string ou um array de objetos para múltiplas tabelas
    targetTable: {
        type: [String, Array],
        required: true
    },
    importOptions: {
        type: Object,
        required: false
    }
});

const emit = defineEmits(['update:open', 'import-complete']);

// Estado
const isOpen = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const selectedClient = ref(null);
const selectedSheet = ref(props.importOptions?.sheet_name);
const isUploading = ref(false);
const uploadProgress = ref(0);
const importStatus = ref('');
const errorMessage = ref('');
const options = reactive({
    headerRow: true,
    skipValidation: false,
    customMapping: false,
    sheets: props.importOptions?.sheets,
    sheet_name: props.importOptions?.sheet_name,
    clients: props.importOptions?.clients,
    client_id: null
});

// Processamento de tabelas disponíveis
const availableTables = computed(() => {
    if (Array.isArray(props.targetTable)) {
        return props.targetTable;
    } else {
        return [
            {
                name: props.targetTable,
                label: formatTableName(props.targetTable),
                description: ''
            }
        ];
    }
});

// Tabela selecionada
const selectedTable = ref(availableTables.value.length === 1 ? availableTables.value[0].name : '');

// Estado para mapeamento de colunas
const showColumnMapping = ref(false);
const tableFields = ref([]);
const excelColumns = ref([]);
const excelPreview = ref([]);
const columnMapping = reactive({});

// Função para formatar o nome da tabela
function formatTableName(tableName) {
    return tableName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Função para obter o rótulo de exibição da tabela
function getTableLabel(tableName) {
    const table = availableTables.value.find(t => t.name === tableName);
    return table ? table.label : formatTableName(tableName);
}

// Método para atualizar estado do modal
function updateOpen(value) {
    isOpen.value = value;
    emit('update:open', value);
}

// Método para lidar com a seleção de arquivo
function handleFileSelect(event) {
    const files = event.target.files;
    if (files.length > 0) {
        selectedFile.value = files[0];
    } else {
        selectedFile.value = null;
    }
}

// Método para lidar com o envio do formulário
async function handleFileSubmit() {
    if (!selectedFile.value || !selectedTable.value) return;

    // Se a opção de mapeamento personalizado estiver ativada e ainda não estiver mostrando
    if (options.customMapping && !showColumnMapping.value) {
        await analyzeExcelFile();
        return;
    }

    // Caso contrário, faça o upload do arquivo
    uploadFile();
}

// Método para analisar o arquivo Excel e obter as colunas
async function analyzeExcelFile() {
    isUploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('targetTable', selectedTable.value);
    formData.append('headerRow', options.headerRow);
    formData.append('sheet_name', selectedSheet.value); 
    try {
        const response = await axios.post('/api/import/analyze', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadProgress.value = percentCompleted;
            }
        });

        isUploading.value = false;

        if (response.data.status === 'success') {
            // Preencher as informações do mapeamento
            tableFields.value = response.data.tableFields;
            excelColumns.value = response.data.excelColumns;
            excelPreview.value = response.data.preview;

            // Inicializar o mapeamento automático
            initializeColumnMapping();

            // Mostrar a interface de mapeamento
            showColumnMapping.value = true;
        } else {
            errorMessage.value = response.data.message || 'Erro ao analisar o arquivo.';
            importStatus.value = 'error';
        }
    } catch (error) {
        isUploading.value = false;
        importStatus.value = 'error';
        errorMessage.value = error.response?.data?.message || 'Erro ao analisar o arquivo Excel.';
    }
}

// Inicializar o mapeamento de colunas com base nas correspondências automáticas
function initializeColumnMapping() {
    // Para cada campo da tabela
    tableFields.value.forEach(field => {
        // Tentar encontrar uma correspondência no Excel
        const matchingColumn = excelColumns.value.find(col =>
            col.label.toLowerCase() === field.name.toLowerCase() ||
            col.label.toLowerCase().includes(field.name.toLowerCase())
        );

        // Definir o mapeamento
        columnMapping[field.name] = matchingColumn ? matchingColumn.value : '';
    });
}

// Método para voltar à seleção de arquivo
function backToFileSelection() {
    showColumnMapping.value = false;
}

// Método para fazer upload do arquivo
async function uploadFile() {
    isUploading.value = true;
    uploadProgress.value = 0;
    importStatus.value = '';
    errorMessage.value = '';

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('targetTable', selectedTable.value);
    // formData.append('client_id', selectedClient.value);
    // formData.append('sheet_name', options.sheet_name);
    // Adicionar opções de importação
    const optionsToSend = {
        ...options,
        columnMapping: showColumnMapping.value ? columnMapping : null
    };

    formData.append('options', JSON.stringify(optionsToSend));

    try {
        const response = await axios.post('/api/import/excel', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                uploadProgress.value = percentCompleted;
            }
        });

        isUploading.value = false;

        if (response.data.status === 'success') {
            importStatus.value = 'queued';
            emit('import-complete', {
                status: 'success',
                table: selectedTable.value,
                jobId: response.data.jobId
            });
        } else {
            importStatus.value = 'error';
            errorMessage.value = response.data.message || 'Ocorreu um erro durante a importação.';
            emit('import-complete', {
                status: 'error',
                table: selectedTable.value,
                message: errorMessage.value
            });
        }
    } catch (error) {
        isUploading.value = false;
        importStatus.value = 'error';
        errorMessage.value = error.response?.data?.message || 'Ocorreu um erro durante o upload do arquivo.';
        emit('import-complete', {
            status: 'error',
            table: selectedTable.value,
            message: errorMessage.value
        });
    }
}

// Status classes com Tailwind
const importStatusClass = computed(() => {
    if (importStatus.value === 'queued' || importStatus.value === 'success') {
        return 'bg-green-50 text-green-700 border border-green-200';
    } else if (importStatus.value === 'error') {
        return 'bg-red-50 text-red-700 border border-red-200';
    }
    return '';
});

// Reiniciar formulário
function resetForm() {
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    selectedFile.value = null;
    selectedClient.value = null;
    isUploading.value = false;
    uploadProgress.value = 0;
    importStatus.value = '';
    errorMessage.value = '';
    showColumnMapping.value = false;
    tableFields.value = [];
    excelColumns.value = [];
    excelPreview.value = [];
    Object.keys(columnMapping).forEach(key => delete columnMapping[key]);
    isOpen.value = false;
}

// Exportar método para abrir o modal
defineExpose({ open: () => isOpen.value = true });
</script>