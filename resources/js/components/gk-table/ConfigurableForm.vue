<template>
    <div v-for="(rowFields, rowIndex) in groupedFields" :key="`row-${rowIndex}`"
        class="grid grid-cols-12 gap-x-4 gap-y-4 items-start">
        <div v-for="field in rowFields" :key="field.name" :class="getColSpanClass(field)">
            <FormFieldWrapper :field="field" :model-value="inertiaForm[field.name]"
                :error="inertiaForm.errors[field.name]" @update:model-value="handleFieldUpdate(field.name, $event)"
                @fieldAction="handleFieldAction" />
        </div>
    </div>
</template>
<script lang="ts" setup>

import type { InertiaForm } from '@inertiajs/vue3' // Tipo correto
import { computed } from 'vue';

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
    (e: 'update:form-values', values: FormValues): void;
    (e: 'fieldAction', action: { name: string; type: string; value?: any }): void;
}>()

const groupedFields = computed(() => {
    const rows: Record<number, FieldConfig[]> = {};
    props.fields.forEach(field => {
        const row = field.row || 0;
        if (!rows[row]) rows[row] = [];
        rows[row].push(field);
    });
    return Object.values(rows);
});
const getColSpanClass = (field: FieldConfig) => {
    const colSpan = field.colSpan || 12;
    return `col-span-${colSpan}`;
};

// Lidar com update do FormFieldWrapper para atualizar o form.data
const handleFieldUpdate = (fieldName: string, newValue: any) => {
    // Emitir evento para o pai atualizar o form
    emit('updateField', fieldName, newValue);
};

// Handler para ações de campo (SmartSelect, etc.)
const handleFieldAction = async (action: string, data: any, sourceFieldName: string) => {
    console.log(`[DynamicForm] Field action from "${sourceFieldName}":`, action, data);
};
</script>