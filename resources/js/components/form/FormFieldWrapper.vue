<script setup lang="ts">
import { defineProps, computed, defineAsyncComponent, watch } from 'vue'
import { useField } from 'vee-validate'
// Assumindo shadcn-vue como peer dependency 
import { cn } from '../../lib/utils' // Path relativo para o utils.ts dentro do pacote
import type { FieldConfig } from './DynamicForm.vue' // Importar tipo do DynamicForm no mesmo diretório

const props = defineProps<{
    field: FieldConfig;
}>()

// Use VeeValidate's useField
const { 
    value: model,
    errorMessage,
} = useField<any>(() => props.field.name);

// Debug log
watch(errorMessage, (newError) => {
    console.log(`[Gatekeeper/FormFieldWrapper:${props.field.name}] errorMessage updated:`, newError);
});

// Dynamically load field components based on field.type
const fieldComponents = {
    text: defineAsyncComponent(() => import('./fields/FormFieldInput.vue')),
    email: defineAsyncComponent(() => import('./fields/FormFieldInput.vue')),
    password: defineAsyncComponent(() => import('./fields/FormFieldInput.vue')),
    number: defineAsyncComponent(() => import('./fields/FormFieldInput.vue')),
    textarea: defineAsyncComponent(() => import('./fields/FormFieldTextarea.vue')),
    select: defineAsyncComponent(() => import('./fields/FormFieldSelect.vue')),
    combobox: defineAsyncComponent(() => import('./fields/FormFieldCombobox.vue')),
    richtext: defineAsyncComponent(() => import('./fields/FormFieldRichText.vue')),
    file: defineAsyncComponent(() => import('./fields/FormFieldFile.vue')),
    image: defineAsyncComponent(() => import('./fields/FormFieldFile.vue')),
    repeater: defineAsyncComponent(() => import('./fields/FormFieldRepeater.vue')),
    modalSelect: defineAsyncComponent(() => import('./fields/FormFieldModalSelect.vue')),
    radio: defineAsyncComponent(() => import('./fields/FormFieldRadioGroup.vue')),
    checkboxList: defineAsyncComponent(() => import('./fields/FormFieldCheckboxList.vue')),
};

const FieldComponent = computed(() => {
    // @ts-ignore
    const fieldType = props.field?.type as keyof typeof fieldComponents;
    return fieldType && fieldComponents[fieldType] ? fieldComponents[fieldType] : null;
});

const fieldId = computed(() => `field-${props.field.name}`);

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
            v-model="model"
            :error="errorMessage" 
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