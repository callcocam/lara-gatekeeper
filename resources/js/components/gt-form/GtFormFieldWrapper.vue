<script setup lang="ts">
import { defineProps, computed, inject, watch } from 'vue'
import { fieldRegistryKey } from '../../injectionKeys';
import type { FieldRegistry } from './fieldRegistry';
import { FieldConfig } from '../../types/field';

const props = defineProps<{
    field: FieldConfig;
    error?: string;
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void;
    (e: 'fieldAction', action: string, data: any, fieldName: string): void;
    (e: 'update:form-values', values: any): void;
}>()

const modelValue = defineModel<any>()
const fieldRegistry = inject(fieldRegistryKey);

const FieldComponent = computed(() => {
    const fieldType = props.field?.type as keyof FieldRegistry;
    if (!fieldRegistry) {
        console.error('[LaraGatekeeper] Field registry not provided!');
        return null;
    }
    if (fieldType && fieldRegistry[fieldType]) {
        return fieldRegistry[fieldType];
    } else {
        console.warn(`[LaraGatekeeper] Field component for type "${fieldType}" not found in registry.`);
        return null;
    }
});

const fieldId = computed(() => `field-${props.field?.name}`);

const updateModelValue = (value: any) => {
    console.log('updateModelValue', value);
    emit('update:modelValue', value);
};

// Handler para eventos de ações de campo (SmartSelect, etc.)
const handleFieldAction = (action: string, data: any) => {
    console.log(`[FormFieldWrapper:${props.field?.name}] Field action:`, action, data);

    // Repassar evento para o formulário pai com informações do campo atual
    emit('fieldAction', action, data, props.field?.name || '');
};

</script>

<template>
    <component v-if="FieldComponent" :is="FieldComponent" :id="fieldId" :field="field"
        :inputProps="{ ...field.inputProps, 'aria-invalid': !!props.error, 'aria-describedby': props.error ? `${fieldId}-error` : undefined }"
        :error="props.error" :modelValue="modelValue" @fieldAction="handleFieldAction"
        @update:modelValue="updateModelValue" />
    <div v-else class="text-sm text-destructive bg-destructive/10 p-2 rounded">
        [Gatekeeper] Componente de campo não encontrado para o tipo: {{ field.type }}
    </div>
</template>