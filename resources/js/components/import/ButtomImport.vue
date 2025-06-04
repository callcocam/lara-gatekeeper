<template>
    <div>
        <Button 
            :variant="variant"
            :size="size"
            @click="emitImportEvent"
            :disabled="isLoading"
        >
            <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
            <Upload v-else class="mr-2 h-4 w-4" />
            <span>{{ buttonText }}</span>
        </Button>
    </div>  
</template>

<script setup>
import { ref, defineProps, defineEmits, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Loader2, Upload } from 'lucide-vue-next';

const props = defineProps({
    buttonText: {
        type: String,
        default: 'Importar'
    },
    importType: {
        type: String,
        default: 'excel'
    },
    // Pode ser uma string ou um array de objetos com { name, label, description }
    targetTable: {
        type: [String, Array],
        required: true
    },
    variant: {
        type: String,
        default: 'default'
    },
    size: {
        type: String,
        default: 'default'
    }
});

// Formatar a lista de tabelas para o formato esperado
const formattedTargetTables = computed(() => {
    if (typeof props.targetTable === 'string') {
        return props.targetTable;
    } else if (Array.isArray(props.targetTable)) {
        // Garantir que cada item tenha os campos necessários
        return props.targetTable.map(table => {
            if (typeof table === 'string') {
                return {
                    name: table,
                    label: formatTableName(table),
                    description: ''
                };
            } else {
                return {
                    name: table.name,
                    label: table.label || formatTableName(table.name),
                    description: table.description || ''
                };
            }
        });
    }
    return props.targetTable;
});

// Função para formatar o nome da tabela
function formatTableName(tableName) {
    return tableName
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

const emit = defineEmits(['import-click']);
const isLoading = ref(false);

function emitImportEvent() {
    emit('import-click', {
        type: props.importType,
        targetTable: formattedTargetTables.value
    });
}
</script>