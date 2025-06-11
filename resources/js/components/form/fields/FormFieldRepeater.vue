<script setup lang="ts">
import { computed, ref } from 'vue'
import FormFieldWrapper from '../FormFieldWrapper.vue' 
import { Trash2, Plus, ChevronDown, ChevronUp } from 'lucide-vue-next'
import type { FieldConfig } from '../DynamicForm.vue'

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

</script>

<template>
    <div class="space-y-4">
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
                        :field="subField" :model-value="getFieldValue(index, subField.name)"
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