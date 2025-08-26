<script setup lang="ts">
import { computed, ref } from 'vue'
import FormFieldWrapper from '../GtFormFieldWrapper.vue' 
import { Trash2, Plus, ChevronDown, ChevronUp } from 'lucide-vue-next' 
import { Button } from '@/components/ui/button'
import { FieldConfig } from '../../../types/field';

// Define props seguindo o padrão dos outros campos
const props = defineProps<{
    field: FieldConfig & {
        subFields: FieldConfig[];
        addButtonLabel?: string;
        deleteButtonLabel?: string;
        minItems?: number;
        maxItems?: number;
        collapsible?: boolean;
        collapsed?: boolean;
        reorderable?: boolean;
        cloneable?: boolean;
    };
    modelValue?: any[] | null;
    error?: string;
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: any[]): void
}>()

// Estado de colapso para cada item
const collapsedItems = ref<Record<number, boolean>>({});

// Valores atuais do repeater
const currentValue = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue;
    }
    // Se for null, undefined ou qualquer outro tipo, retorna array vazio
    return [];
});

// Compute default values for a new item
const defaultItemValues = computed(() => {
    const defaults: Record<string, any> = {};
    props.field.subFields?.forEach(subField => {
        // Verifica se há defaultValue nas inputProps primeiro
        if (subField.inputProps?.defaultValue !== undefined) {
            defaults[subField.name] = subField.inputProps.defaultValue;
        } else {
            // Valores padrão baseados no tipo
            switch (subField.type) {
                case 'number':
                    defaults[subField.name] = 0;
                    break;
                case 'boolean':
                case 'switch':
                    defaults[subField.name] = false;
                    break;
                case 'select':
                case 'combobox':
                    defaults[subField.name] = '';
                    break;
                default:
                    defaults[subField.name] = '';
            }
        }
    });
    console.log(`[Gatekeeper/Repeater:${props.field.name}] Default item values:`, defaults);
    return defaults;
});

const addButtonLabel = computed(() => props.field.addButtonLabel || 'Adicionar Item');
const deleteButtonLabel = computed(() => props.field.deleteButtonLabel || 'Remover');

// Verifica se o modo colapsável está ativo
const isCollapsible = computed(() => props.field.collapsible !== false);

// Verifica se deve iniciar colapsado
const shouldStartCollapsed = computed(() => props.field.collapsed === true);

// Verifica se pode adicionar mais itens
const canAddItem = computed(() => {
    if (props.field.maxItems) {
        return currentValue.value.length < props.field.maxItems;
    }
    return true;
});

// Verifica se pode remover itens
const canRemoveItem = computed(() => {
    if (props.field.minItems) {
        return currentValue.value.length > props.field.minItems;
    }
    return currentValue.value.length > 0;
});

// Inicializa o estado de colapso para novos itens
const initializeCollapsedState = (index: number) => {
    if (isCollapsible.value && !(index in collapsedItems.value)) {
        collapsedItems.value[index] = shouldStartCollapsed.value;
    }
};

// Toggle do estado de colapso
const toggleCollapsed = (index: number) => {
    if (isCollapsible.value) {
        collapsedItems.value[index] = !collapsedItems.value[index];
    }
};

// Verifica se um item está colapsado
const isItemCollapsed = (index: number) => {
    if (!isCollapsible.value) return false;
    initializeCollapsedState(index);
    return collapsedItems.value[index] || false;
};

const handleAddItem = () => {
    if (!canAddItem.value) return;
    
    const newItem = { ...defaultItemValues.value };
    const newValue = [...currentValue.value, newItem];
    
    console.log(`[Gatekeeper/Repeater:${props.field.name}] Adding new item:`, newItem);
    emit('update:modelValue', newValue);
    
    // Inicializa o estado de colapso para o novo item
    const newIndex = newValue.length - 1;
    if (isCollapsible.value) {
        collapsedItems.value[newIndex] = shouldStartCollapsed.value;
    }
}

const handleRemoveItem = (index: number) => {
    if (!canRemoveItem.value) return;
    
    const newValue = currentValue.value.filter((_, i) => i !== index);
     console.log(`[Gatekeeper/Repeater:${props.field.name}] Removing item at index:`, index);
    emit('update:modelValue', newValue);
    
    // Remove o estado de colapso do item removido e reajusta os índices
    const newCollapsedItems: Record<number, boolean> = {};
    Object.keys(collapsedItems.value).forEach(key => {
        const idx = parseInt(key);
        if (idx < index) {
            newCollapsedItems[idx] = collapsedItems.value[idx];
        } else if (idx > index) {
            newCollapsedItems[idx - 1] = collapsedItems.value[idx];
        }
    });
    collapsedItems.value = newCollapsedItems;
}

const handleFieldUpdate = (itemIndex: number, fieldName: string, newValue: any) => {
    const updatedItems = [...currentValue.value];
    if (!updatedItems[itemIndex]) {
        updatedItems[itemIndex] = {};
    }
    updatedItems[itemIndex][fieldName] = newValue;
    
    console.log(`[Gatekeeper/Repeater:${props.field.name}] Field update [${itemIndex}].${fieldName}:`, newValue);
    emit('update:modelValue', updatedItems);
}

// Obter valor de um campo específico
const getFieldValue = (itemIndex: number, fieldName: string) => {
    return currentValue.value[itemIndex]?.[fieldName] ?? '';
}

// Gerar título do item baseado nos campos
const getItemTitle = (item: any, index: number) => {
    // Se há apenas um campo, usa seu valor como título
    if (props.field.subFields && props.field.subFields.length === 1) {
        const field = props.field.subFields[0];
        const value = item[field.name];
        return value ? `${field.label}: ${value}` : `${field.label}`;
    }
    
    // Caso contrário, usa "Item X"
    return `Item ${index + 1}`;
};

// Função para enriquecer campos com contexto do workflow
const enrichFieldWithWorkflowContext = (subField: any, itemIndex: number) => {
    // Se não é um workflowStepCalculator, retorna o campo original
    if (subField.type !== 'workflowStepCalculator') {
        return subField;
    }
    
    // Para workflowStepCalculator, adiciona contexto dinâmico
    const enrichedField = { ...subField };
    
    // Define a ordem da etapa (1-based)
    enrichedField.stepOrder = itemIndex + 1;
    
    // Define os dados da etapa anterior (se existir)
    if (itemIndex > 0) {
        const previousItem = currentValue.value[itemIndex - 1];
        if (previousItem && previousItem[subField.name]) {
            enrichedField.previousStepData = previousItem[subField.name];
        } else {
            enrichedField.previousStepData = null;
        }
    } else {
        enrichedField.previousStepData = null;
    }
    
    console.log(`[Gatekeeper/Repeater:${props.field.name}] Enriching workflow field [${itemIndex}]:`, {
        stepOrder: enrichedField.stepOrder,
        previousStepData: enrichedField.previousStepData
    });
    
    return enrichedField;
};

// Computed para verificar se é um repeater de workflow
const isWorkflowRepeater = computed(() => {
    return props.field.subFields?.some(subField => subField.type === 'workflowStepCalculator');
});

// Computed para resumo das etapas do workflow
const workflowSummary = computed(() => {
    if (!isWorkflowRepeater.value) return null;
    
    const steps = currentValue.value.map((item, index) => {
        const stepData = item[props.field.subFields?.[0]?.name];
        return {
            order: index + 1,
            hasTemplate: stepData?.template_id ? true : false,
            hasExpectedDate: stepData?.expected_date ? true : false,
            hasCompletedDate: stepData?.completed_date ? true : false,
            templateName: stepData?.template_name || null
        };
    });
    
    const totalSteps = steps.length;
    const configuredSteps = steps.filter(step => step.hasTemplate).length;
    const stepsWithDates = steps.filter(step => step.hasExpectedDate).length;
    
    return {
        totalSteps,
        configuredSteps,
        stepsWithDates,
        steps
    };
});

</script>

<template>
    <div class="space-y-4">
        <!-- Timeline Header - Resumo Elegante do Workflow -->
        <div v-if="isWorkflowRepeater && workflowSummary && workflowSummary.totalSteps > 0" 
            class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm">
            
            <!-- Cabeçalho do Timeline -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Timeline do Workflow</h3>
                        <p class="text-sm text-gray-600">Acompanhe o progresso das etapas</p>
                    </div>
                </div>
                
                <!-- Progress Ring -->
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200" />
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                            class="text-blue-500" 
                            :stroke-dasharray="`${(workflowSummary.configuredSteps / workflowSummary.totalSteps) * 175.93} 175.93`" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold text-gray-700">
                            {{ Math.round((workflowSummary.configuredSteps / workflowSummary.totalSteps) * 100) }}%
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600">{{ workflowSummary.totalSteps }}</div>
                    <div class="text-xs text-gray-600 font-medium">Etapas Criadas</div>
                </div>
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-green-100">
                    <div class="text-2xl font-bold text-green-600">{{ workflowSummary.configuredSteps }}</div>
                    <div class="text-xs text-gray-600 font-medium">Configuradas</div>
                </div>
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-purple-100">
                    <div class="text-2xl font-bold text-purple-600">{{ workflowSummary.stepsWithDates }}</div>
                    <div class="text-xs text-gray-600 font-medium">Com Datas</div>
                </div>
            </div>
            
            <!-- Timeline Visual das Etapas -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                <div v-for="(step, index) in workflowSummary.steps" :key="step.order" class="flex items-center">
                    <!-- Círculo da etapa -->
                    <div class="flex flex-col items-center">
                        <div :class="[
                            'w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition-all',
                            step.hasTemplate 
                                ? 'bg-green-500 border-green-500 text-white shadow-lg' 
                                : 'bg-white border-gray-300 text-gray-400'
                        ]">
                            <span v-if="step.hasTemplate">✓</span>
                            <span v-else>{{ step.order }}</span>
                        </div>
                        <span class="text-xs text-gray-600 mt-1 font-medium">{{ step.order }}</span>
                    </div>
                    
                    <!-- Linha conectora -->
                    <div v-if="index < workflowSummary.steps.length - 1" 
                        :class="[
                            'w-8 h-0.5 mx-1',
                            step.hasTemplate ? 'bg-green-300' : 'bg-gray-300'
                        ]">
                    </div>
                </div>
            </div>
        </div>

        <!-- Items existentes -->
        <div v-for="(item, index) in currentValue" :key="`item-${index}`"
            class="border rounded-md bg-background shadow-sm">
            
            <!-- Header do item (sempre visível se colapsável) -->
            <div v-if="isCollapsible" 
                class="flex items-center justify-between p-3 cursor-pointer hover:bg-muted/50 transition-colors"
                @click="toggleCollapsed(index)">
                
                <div class="flex items-center space-x-2">
                    <component :is="isItemCollapsed(index) ? ChevronDown : ChevronUp" 
                        class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">{{ getItemTitle(item, index) }}</span>
                </div>
                
                <!-- Botão de remover no header -->
                <Button v-if="canRemoveItem" variant="ghost" size="icon" 
                    @click.stop="handleRemoveItem(index)"
                    class="h-6 w-6 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                    :aria-label="`${deleteButtonLabel} item ${index + 1}`" type="button">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>

            <!-- Conteúdo do item -->
            <div v-show="!isCollapsible || !isItemCollapsed(index)" 
                :class="[
                    'space-y-3 relative',
                    isCollapsible ? 'p-3 pt-0' : 'p-4'
                ]">
                
                <!-- Botão de remover (quando não colapsável) -->
                <Button v-if="!isCollapsible && canRemoveItem" variant="ghost" size="icon" 
                    @click="handleRemoveItem(index)"
                    class="absolute top-2 right-2 h-6 w-6 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                    :aria-label="`${deleteButtonLabel} item ${index + 1}`" type="button">
                <Trash2 class="h-4 w-4" />
            </Button>

                <!-- Título do item (quando não colapsável) -->
                <p v-if="!isCollapsible && field.subFields && field.subFields.length > 1" 
                    class="text-sm font-medium mb-3 pr-8">
                    Item {{ index + 1 }}
                </p>

                <!-- Campos do item -->
            <div class="grid grid-cols-12 gap-x-4 gap-y-2 items-start">
                    <FormFieldWrapper v-for="subField in field.subFields" :key="`${index}-${subField.name}`"
                        :field="enrichFieldWithWorkflowContext(subField, index)" :model-value="getFieldValue(index, subField.name)"
                        @update:model-value="handleFieldUpdate(index, subField.name, $event)" :class="[
                        'col-span-12',
                        subField.colSpan ? `md:col-span-${subField.colSpan}` : 'md:col-span-12'
                        ]" />
                </div>
            </div>
        </div>

        <!-- Mensagem quando não há itens -->
        <div v-if="currentValue.length === 0"
            class="text-center py-8 text-muted-foreground border-2 border-dashed rounded-md">
            <p class="text-sm">Nenhum item adicionado ainda</p>
        </div>

        <!-- Botão de adicionar -->
        <div v-if="canAddItem" class="flex justify-center mt-4">
            <Button type="button" variant="default" size="sm" @click="handleAddItem" class="flex items-center" >
                <Plus class="h-4 w-4 mr-2" />
           <span>{{ addButtonLabel }}</span>
        </Button>
        </div>

        <!-- Informação sobre limites -->
        <div v-if="field.minItems || field.maxItems" class="text-xs text-muted-foreground">
            <span v-if="field.minItems">Mínimo: {{ field.minItems }}</span>
            <span v-if="field.minItems && field.maxItems"> • </span>
            <span v-if="field.maxItems">Máximo: {{ field.maxItems }}</span>
        </div>
    </div>
</template> 