<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue'
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

const emit = defineEmits<{ (e: 'updateField', fieldName: string, newValue: any): void, (e: 'submit', event: Event): void }>() // Definir novo emit

// Manter groupedFields se o layout por linha for desejado
const groupedFields = computed(() => { 
    const groups: Record<number, FieldConfig[]> = {};
    if (Array.isArray(props.fields)) {
        props.fields.forEach(field => {
            const row = field.row || 0;
            if (!groups[row]) {
                groups[row] = [];
            }
            groups[row].push(field);
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
    console.log(`[Gatekeeper/DynamicForm] Field update "${fieldName}":`, newValue);
    // Emitir evento para o pai atualizar o form
    emit('updateField', fieldName, newValue);
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
                /> 
            </div>
        </div>

        <!-- O slot actions agora não precisa mais receber :submit -->
        <slot name="actions">
        </slot>
    </form>
</template>