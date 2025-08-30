<script setup lang="ts">
import { computed, ref, watch } from 'vue'
// Assumir que Checkbox e Label vêm de peer dependencies (a aplicação principal deve tê-los) 
import { Checkbox } from '@/components/ui/checkbox' // Ajustar path se necessário na app principal
import { Label } from '@/components/ui/label'     // Ajustar path se necessário na app principal
import { Input } from '@/components/ui/input'       // Adicionado Input para o filtro
import { cn } from '../../../lib/utils' 

// Define props
const props = defineProps<{
    id: string;
    field: {
        name: string;
        options: Record<string, string>; // Required: { value: label }
        gridCols?: number; // Nova prop para definir colunas do grid
        [key: string]: any;
    };
    inputProps?: Record<string, any>; 
    error?: string;
}>()

// Use defineModel, forçando a ser um array ou null
const model = defineModel<(string | number)[] | null>()

// Internal ref to always work with an array
const selectedValues = ref<(string | number)[]>([]) 
// Log para debug
const logName = computed(() => `[Gatekeeper/CheckboxList:${props.field.name || props.id}]`);

// Sync internal array with external model ONCE initially and when model changes
watch(model, (newModelValue) => {
    // Sempre tratar como array, mesmo que seja null
    selectedValues.value = Array.isArray(newModelValue) ? [...newModelValue] : []; 
}, { immediate: true }); 

// Sync internal changes back to the external model
watch(selectedValues, (newSelectedArray) => {
    // Atualizar o modelo externo apenas se realmente for diferente
    const currentModelArray = Array.isArray(model.value) ? model.value : [];
    
    // Comparar com base nos valores reais, não na ordem
    const isSameContent = 
        newSelectedArray.length === currentModelArray.length && 
        newSelectedArray.every(value => 
            currentModelArray.some(v => String(v) === String(value))
        );
    
    if (!isSameContent) { 
        // Precisamos criar um novo array para garantir a reatividade
        model.value = newSelectedArray.length > 0 ? [...newSelectedArray] : null;
    }
}, { deep: true });

// --- Filter Logic ---
const searchTerm = ref('')

const options = computed(() => props.field.options || {});

const filteredOptions = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) {
        return options.value; // Retorna todas as opções se o termo estiver vazio
    }
    
    const filtered: Record<string, string> = {};
    for (const value in options.value) {
        const label = options.value[value];
        if (label.toLowerCase().includes(term)) {
            filtered[value] = label;
        }
    }
    return filtered;
});
// --- End Filter Logic ---

// --- Layout Logic ---
const layoutClass = computed(() => {
    const cols = props.field.gridCols;
    if (cols && cols > 1) {
        // Gerar classe de grid responsiva. Ex: max 4 colunas por padrão
        const maxCols = Math.min(cols, 4); // Limitar a 4 por padrão, pode ser ajustado
        return `grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 md:grid-cols-${maxCols}`;
    } else {
        // Layout vertical padrão se gridCols não for > 1
        return 'space-y-2'; 
    }
});
// --- End Layout Logic ---

// --- Select All Logic --- (Opera sobre todas as opções, não apenas as filtradas)
const allOptionValues = computed(() => Object.keys(options.value)); // Baseado nas opções originais

const isAllSelected = computed(() => {
    // Se não houver opções, não há nada selecionado
    if (allOptionValues.value.length === 0) return false;
    
    // Checar se todos os valores possíveis estão selecionados
    const allSelected = allOptionValues.value.length === selectedValues.value.length && 
        allOptionValues.value.every(val => 
            selectedValues.value.some(selected => String(selected) === String(val))
        );
    
    return allSelected;
});

const isSomeSelected = computed(() => {
    return selectedValues.value.length > 0 && !isAllSelected.value;
});

// Adjusted to receive boolean or indeterminate from @update:modelValue
function handleSelectAllChange(newValue: boolean | 'indeterminate') {
    console.log(`${logName.value} Select All emitted modelValue:`, newValue);
    // Treat indeterminate as false (deselect all)
    if (newValue === true) {
        // Selecionar todos - usar as chaves de TODAS as opções
        selectedValues.value = [...allOptionValues.value];
    } else {
        // Deselecionar todos
        selectedValues.value = [];
    }
    
    console.log(`${logName.value} Valores após Select All:`, selectedValues.value);
    }

// Adjusted to potentially receive boolean or indeterminate from @update:modelValue
// Although individual checkboxes should likely only emit boolean
function handleCheckboxChange(newValue: boolean | 'indeterminate', value: string | number) {
    console.log(`${logName.value} Individual change emitted modelValue: ${newValue} for value: ${value}`);
    const stringValue = String(value); // Work with strings for Set operations
    const currentSelectedSet = new Set(selectedValues.value.map(String));

    // Only add if newValue is true, remove otherwise (handles false and indeterminate)
    if (newValue === true) {
        currentSelectedSet.add(stringValue);
    } else {
        currentSelectedSet.delete(stringValue);
    }
    
    // Update the selectedValues array, keeping original string types
    selectedValues.value = Array.from(currentSelectedSet);

    console.log(`${logName.value} Valores após mudança:`, selectedValues.value);
}

// Verificar diretamente se um valor está selecionado
function isSelected(value: string | number): boolean {
    return selectedValues.value.some(v => String(v) === String(value));
}
</script>

<template>
    <div :id="props.id" :class="cn('py-1')"> 
        <!-- Filter Input -->
        <div class="mb-3">
            <Input 
                type="text"
                v-model="searchTerm"
                placeholder="Filtrar permissões..."
                class="h-8"
            />
        </div>
        
        <!-- Select All Checkbox -->
        <div v-if="allOptionValues.length > 1" class="flex items-center space-x-2 pb-2 border-b mb-2">
            <Checkbox
                :id="`${props.id}-select-all`"
                :modelValue="isAllSelected ? true : (isSomeSelected ? 'indeterminate' : false)"
                @update:modelValue="handleSelectAllChange" 
            />
            <Label :for="`${props.id}-select-all`" class="font-medium cursor-pointer">
                Selecionar Todos
            </Label>
        </div>

        <!-- Options List (Applies layoutClass) -->
        <div :class="cn(layoutClass)">
        <div 
                v-for="(label, value) in filteredOptions" 
            :key="value"
            class="flex items-center space-x-2"
        >
            <Checkbox 
                :id="`${props.id}-${value}`" 
                    :modelValue="isSelected(value)"
                    @update:modelValue="(newVal: boolean | 'indeterminate') => handleCheckboxChange(newVal, value)" 
                v-bind="props.inputProps"
            />
            <Label :for="`${props.id}-${value}`" class="font-normal cursor-pointer">
                {{ label }}
            </Label>
            </div>
            <!-- Mensagem se nenhum item for encontrado -->
             <div v-if="Object.keys(filteredOptions).length === 0 && searchTerm"
                  class="text-sm text-muted-foreground py-2">
                 Nenhuma permissão encontrada para "{{ searchTerm }}".
             </div>
        </div>
    </div>
</template> 