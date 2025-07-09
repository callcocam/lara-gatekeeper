<script setup lang="ts">
import { defineProps, computed, inject } from 'vue'
import { cn } from '../../lib/utils'
import type { FieldConfig } from './DynamicForm.vue'
import { fieldRegistryKey } from '../../injectionKeys';
import type { FieldRegistry } from './fieldRegistry';
import { Label } from '@/components/ui/label'

const props = defineProps<{
    field: FieldConfig;
    error?: string;
    modelValue: any;
}>() 
const emit = defineEmits<{ 
    (e: 'update:modelValue', value: any): void;
    (e: 'fieldAction', action: string, data: any, fieldName: string): void;
    (e: 'update:form-values', values: any): void;
}>()

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
    emit('update:modelValue', value);
};

// Handler para eventos de ações de campo (SmartSelect, etc.)
const handleFieldAction = (action: string, data: any) => {
    console.log(`[FormFieldWrapper:${props.field?.name}] Field action:`, action, data);
    
    // Repassar evento para o formulário pai com informações do campo atual
    emit('fieldAction', action, data, props.field?.name || '');
};

const updateFormValues = (values: any) => {
    emit('update:form-values', values);
};
</script>

<template>
    <div class="space-y-2">
        <Label v-if="!props.field?.hideLabel" :for="fieldId" :class="cn(props.error && 'text-destructive')">
            {{ field.label }}
            <span v-if="field.required" class="text-destructive"> *</span>
        </Label>

        <component v-if="FieldComponent" :is="FieldComponent" :id="fieldId" :field="field"
            :inputProps="{ ...field.inputProps, 'aria-invalid': !!props.error, 'aria-describedby': props.error ? `${fieldId}-error` : undefined }"
            :error="props.error" :modelValue="props.modelValue" 
            @update:modelValue="updateModelValue" 
            @fieldAction="handleFieldAction"
            @update:form-values="updateFormValues" />
        <div v-else class="text-sm text-destructive bg-destructive/10 p-2 rounded">
            [Gatekeeper] Componente de campo não encontrado para o tipo: {{ field.type }}
        </div>
        <p v-if="field.description && !props.error" class="text-sm text-muted-foreground">
            {{ field.description }}
        </p>
        <p v-if="props.error" :id="`${fieldId}-error`" class="text-sm font-medium text-destructive">
            {{ props.error }}
        </p>
    </div>
</template> 