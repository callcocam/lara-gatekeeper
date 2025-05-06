<script setup lang="ts">
import { defineProps, computed, inject } from 'vue'
import { cn } from '../../lib/utils'
import type { FieldConfig } from './DynamicForm.vue'
import { fieldRegistryKey } from '../../injectionKeys';
import type { FieldRegistry } from './fieldRegistry';

const props = defineProps<{
    field: FieldConfig;
    error?: string;
    modelValue: any;
}>()

const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>()

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
</script>

<template>
    <div class="space-y-2">
        <Label v-if="props.field?.label" :for="fieldId" :class="cn(props.error && 'text-destructive')">
            {{ field.label }}
            <span v-if="field.required" class="text-destructive"> *</span>
        </Label>

        <component v-if="FieldComponent" :is="FieldComponent" :id="fieldId" :field="field"
            :inputProps="{ ...field.inputProps, 'aria-invalid': !!props.error, 'aria-describedby': props.error ? `${fieldId}-error` : undefined }"
            :error="props.error" :modelValue="props.modelValue" @update:modelValue="updateModelValue" />
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