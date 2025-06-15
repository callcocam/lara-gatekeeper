<script setup lang="ts">
import { ref, watch, computed, nextTick, reactive } from 'vue'
// Remover imports do vee-validate
// import { useForm } from 'vee-validate'
import FormFieldWrapper from './FormFieldWrapper.vue' 
import type { InertiaForm } from '@inertiajs/vue3' // Tipo correto
// DO NOT import types from self

// DEFINE and EXPORT types locally (ou mover para um arquivo types.ts dentro do pacote)
export interface FieldConfig {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    description?: string;
    options?: Record<string, string>;
    row?: number;
    colSpan?: number;
    // Allow any other props needed by specific field components
    [key: string]: any;
}

export interface FormErrors { 
    [key: string]: string; // Erros do Inertia são string, não array
}

export interface FormValues {
    [key: string]: any;
}

const props = defineProps<{
    fields: FieldConfig[];
    inertiaForm: InertiaForm<Record<string, any>>; // Receber o form do Inertia
}>()

const emit = defineEmits<{ 
    (e: 'updateField', fieldName: string, newValue: any): void;
    (e: 'submit', event: Event): void;
}>() // Definir novo emit

// Estado para controlar visibilidade e comportamento dos campos
const fieldStates = reactive<Record<string, {
    visible: boolean;
    disabled: boolean;
    loading: boolean;
}>>({});

// Inicializar estados dos campos
const initializeFieldStates = () => {
    props.fields.forEach(field => {
        if (!fieldStates[field.name]) {
            fieldStates[field.name] = {
                visible: true,
                disabled: field.disabled || false,
                loading: false
            };
        }
    });
};

// Inicializar quando os campos mudarem
watch(() => props.fields, initializeFieldStates, { immediate: true });

// Manter groupedFields se o layout por linha for desejado
const groupedFields = computed(() => { 
    const groups: Record<number, FieldConfig[]> = {};
    if (Array.isArray(props.fields)) {
        props.fields.forEach(field => {
            // Só incluir campos visíveis
            if (fieldStates[field.name]?.visible !== false) {
                const row = field.row || 0;
                if (!groups[row]) {
                    groups[row] = [];
                }
                groups[row].push(field);
            }
        });
    }
    return Object.entries(groups)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([_, fieldsInRow]) => fieldsInRow);
});

// Manter getColSpanClass
const getColSpanClass = (field: FieldConfig): string => {
    const span = field.colSpan ? Math.max(1, Math.min(12, field.colSpan)) : 12;
    return `col-span-12 md:col-span-${span}`;
};

// Lidar com update do FormFieldWrapper para atualizar o form.data
const handleFieldUpdate = (fieldName: string, newValue: any) => { 
    // Emitir evento para o pai atualizar o form
    emit('updateField', fieldName, newValue);
};

// Handler para ações de campo (SmartSelect, etc.)
const handleFieldAction = async (action: string, data: any, sourceFieldName: string) => {
    console.log(`[DynamicForm] Field action from "${sourceFieldName}":`, action, data);
    
    switch (action) {
        case 'showFields':
            if (Array.isArray(data)) {
                data.forEach(fieldName => {
                    if (fieldStates[fieldName]) {
                        fieldStates[fieldName].visible = true;
                        console.log(`[DynamicForm] Showing field: ${fieldName}`);
                    }
                });
            }
            break;
            
        case 'hideFields':
            if (Array.isArray(data)) {
                data.forEach(fieldName => {
                    if (fieldStates[fieldName]) {
                        fieldStates[fieldName].visible = false;
                        console.log(`[DynamicForm] Hiding field: ${fieldName}`);
                    }
                });
            }
            break;
            
        case 'populateField':
            if (data.fieldName && data.value !== undefined) {
                console.log(`[DynamicForm] Populating field "${data.fieldName}" with:`, data.value);
                emit('updateField', data.fieldName, data.value);
            }
            break;
            
        case 'selectField':
            if (data.fieldName && data.value !== undefined) {
                console.log(`[DynamicForm] Selecting value in field "${data.fieldName}":`, data.value);
                emit('updateField', data.fieldName, data.value);
            }
            break;
            
        case 'apiCall':
            if (data.url) {
                console.log(`[DynamicForm] Making API call to: ${data.url}`);
                await handleApiCall(data.url, data.value, data.option, sourceFieldName);
            }
            break;
            
        case 'loadRelated':
            if (data.url) {
                console.log(`[DynamicForm] Loading related data from: ${data.url}`);
                await handleLoadRelated(data.url, data.value, data.option, sourceFieldName);
            }
            break;
            
        case 'callback':
            if (data.callback) {
                console.log(`[DynamicForm] Executing callback: ${data.callback}`);
                handleCallback(data.callback, data.value, data.option, sourceFieldName);
            }
            break;
            
        default:
            console.warn(`[DynamicForm] Unknown field action: ${action}`);
    }
};

// Handler para chamadas de API
const handleApiCall = async (url: string, value: any, option: any, sourceFieldName: string) => {
    try {
        fieldStates[sourceFieldName].loading = true;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ value, option, sourceField: sourceFieldName })
        });
        
        if (!response.ok) throw new Error('API call failed');
        
        const result = await response.json();
        console.log(`[DynamicForm] API call result:`, result);
        
        // Processar resultado da API
        if (result.populateFields) {
            Object.entries(result.populateFields).forEach(([fieldName, fieldValue]) => {
                emit('updateField', fieldName, fieldValue);
            });
        }
        
    } catch (error) {
        console.error(`[DynamicForm] API call error:`, error);
    } finally {
        fieldStates[sourceFieldName].loading = false;
    }
};

// Handler para carregar dados relacionados
const handleLoadRelated = async (url: string, value: any, option: any, sourceFieldName: string) => {
    try {
        fieldStates[sourceFieldName].loading = true;
        
        // Substituir {value} na URL
        const finalUrl = url.replace('{value}', encodeURIComponent(value));
        
        const response = await fetch(finalUrl, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Load related failed');
        
        const result = await response.json();
        console.log(`[DynamicForm] Load related result:`, result);
        
        // Processar dados relacionados
        if (result.data && Array.isArray(result.data)) {
            // Atualizar opções de outros campos se necessário
            // Isso pode ser expandido conforme necessidade
        }
        
    } catch (error) {
        console.error(`[DynamicForm] Load related error:`, error);
    } finally {
        fieldStates[sourceFieldName].loading = false;
    }
};

// Handler para callbacks JavaScript
const handleCallback = (callbackName: string, value: any, option: any, sourceFieldName: string) => {
    try {
        // Verificar se existe uma função global com esse nome
        if (typeof window !== 'undefined' && (window as any)[callbackName]) {
            (window as any)[callbackName](value, option, sourceFieldName, {
                showFields: (fields: string[]) => handleFieldAction('showFields', fields, sourceFieldName),
                hideFields: (fields: string[]) => handleFieldAction('hideFields', fields, sourceFieldName),
                populateField: (fieldName: string, fieldValue: any) => handleFieldAction('populateField', { fieldName, value: fieldValue }, sourceFieldName),
                selectField: (fieldName: string, fieldValue: any) => handleFieldAction('selectField', { fieldName, value: fieldValue }, sourceFieldName),
            });
        } else {
            console.warn(`[DynamicForm] Callback function "${callbackName}" not found in window`);
        }
    } catch (error) {
        console.error(`[DynamicForm] Callback execution error:`, error);
    }
};

</script>

<template>
    <!-- Remover @submit.prevent para permitir que o evento borbulhe -->
    <form class="space-y-4" @submit.prevent="$emit('submit', $event)"> 
        <div v-for="(rowFields, rowIndex) in groupedFields" :key="`row-${rowIndex}`"
            class="grid grid-cols-12 gap-x-4 gap-y-4 items-start">
            <div v-for="field in rowFields" :key="field.name" :class="getColSpanClass(field)">                 
                <FormFieldWrapper 
                    :field="field" 
                    :model-value="inertiaForm[field.name]"  
                    :error="inertiaForm.errors[field.name]"
                    @update:model-value="handleFieldUpdate(field.name, $event)"
                    @fieldAction="handleFieldAction"
                /> 
            </div>
        </div>
        <slot name="fields"></slot>

        <!-- O slot actions agora não precisa mais receber :submit -->
        <slot name="actions">
        </slot>
    </form>
</template>