<script setup lang="ts">
import { defineProps, computed, defineAsyncComponent, watch, inject } from 'vue'
import { useField } from 'vee-validate'
// Assumindo shadcn-vue como peer dependency 
import { cn } from '../../lib/utils' // Path relativo para o utils.ts dentro do pacote
import type { FieldConfig } from './DynamicForm.vue' // Importar tipo do DynamicForm no mesmo diretório
import { fieldRegistryKey } from '../../injectionKeys'; // Importar a chave de injeção
import type { FieldRegistry } from './fieldRegistry'; // Importar tipo (opcional)

const props = defineProps<{
    field: FieldConfig;
}>()

// Injetar o registro de campos
const fieldRegistry = inject(fieldRegistryKey);

// Use computed for the field key to make it reactive and safe
const fieldKey = computed(() => props.field?.key); 

// Use VeeValidate's useField with the computed key
const { 
    value: model,
    errorMessage,
} = useField<any>(fieldKey); 

// Modificar FieldComponent para usar o registro injetado
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
        return null; // Ou um componente de fallback
    }
});

// Also use optional chaining for fieldId, just in case
const fieldId = computed(() => `field-${props.field?.key}`);

</script>

<template>
    <div class="space-y-2">
        <Label 
            v-if="props.field?.label" 
            :for="fieldId" 
            :class="cn(errorMessage && 'text-destructive')"
        >
            {{ field.label }}
            <span v-if="field.required" class="text-destructive"> *</span>
        </Label>

        <component
            v-if="FieldComponent"
            :is="FieldComponent"
            :id="fieldId"
            :field="field"
            :inputProps="{ ...field.inputProps, 'aria-invalid': !!errorMessage, 'aria-describedby': errorMessage ? `${fieldId}-error` : undefined }"
            :error="errorMessage"
            :modelValue="model" 
            @update:modelValue="model = $event"
        />
        <div v-else class="text-sm text-destructive bg-destructive/10 p-2 rounded">
            [Gatekeeper] Componente de campo não encontrado para o tipo: {{ field.type }}
        </div>

        <p v-if="field.description && !errorMessage" class="text-sm text-muted-foreground">
            {{ field.description }}
        </p>
        <p v-if="errorMessage" :id="`${fieldId}-error`" class="text-sm font-medium text-destructive">
            {{ errorMessage }}
        </p>
    </div>
</template> 