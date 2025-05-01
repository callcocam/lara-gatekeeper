<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useForm } from 'vee-validate'
// @ts-ignore
import FormFieldWrapper from './FormFieldWrapper.vue' // Path relativo dentro do pacote
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

export interface FormErrors { // TODO: Considerar usar/alinhar com tipo de erro do Inertia/Laravel
    [key: string]: string[];
}

export interface FormValues {
    [key: string]: any;
}

const props = defineProps<{
    fields: FieldConfig[];
    initialValues?: FormValues;
    errors?: FormErrors;
}>()

const emit = defineEmits<{ (e: 'submit', values: FormValues): void }>()

// Use VeeValidate's useForm, destructure setFieldValue
const { values, setValues, handleSubmit, setFieldError, errors: formErrors, setFieldValue } = useForm<FormValues>({
    initialValues: props.initialValues || {},
});

// DEBUG: Watch for changes in the main values object
watch(values, (newValues) => {
    console.log('[Gatekeeper/DynamicForm] values updated:', JSON.stringify(newValues));
}, { deep: true });

// Watch for external errors and update VeeValidate form errors
watch(() => props.errors, (newErrors) => {
    if (newErrors) {
        console.log('[Gatekeeper/DynamicForm] Received backend errors:', JSON.stringify(newErrors));
        Object.keys(newErrors).forEach(field => {
             // Limpar erro anterior antes de definir um novo (ou nenhum)
            setFieldError(field, undefined);
            if (newErrors[field] && newErrors[field].length > 0) {
                const errorMessage = newErrors[field][0]; // Pega a primeira mensagem
                console.log(`[Gatekeeper/DynamicForm] Setting error for field "${field}":`, errorMessage);
                setFieldError(field, errorMessage);
            }
        });
         // Limpar erros de campos que não vieram no novo objeto de erros
         Object.keys(formErrors.value).forEach(existingErrorField => {
            if (!newErrors || !newErrors[existingErrorField]) {
                 console.log(`[Gatekeeper/DynamicForm] Clearing error for field "${existingErrorField}"`);
                 setFieldError(existingErrorField, undefined);
            }
         });
    } else {
        // Se props.errors se tornar null ou undefined, limpar todos os erros
        console.log('[Gatekeeper/DynamicForm] Clearing all backend errors.');
        Object.keys(formErrors.value).forEach(existingErrorField => {
            setFieldError(existingErrorField, undefined);
        });
    }
}, { deep: true });

// Function to handle form submission
const onSubmit = handleSubmit((formData) => {
    emit('submit', formData)
});

// Initialize form values if initialValues change after mount
watch(() => props.initialValues, (newInitialValues) => {
    if (newInitialValues) {
        setValues(newInitialValues);
    }
}, { deep: true, immediate: true });

// Group fields by row number for layout
const groupedFields = computed(() => {
    const groups: Record<number, FieldConfig[]> = {};
    // Garante que fields é um array antes de iterar
    if (Array.isArray(props.fields)) {
        props.fields.forEach(field => {
            const row = field.row || 0;
            if (!groups[row]) {
                groups[row] = [];
            }
            groups[row].push(field);
        });
    }
    // Sort rows numerically
    return Object.entries(groups)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([_, fieldsInRow]) => fieldsInRow);
});

// Function to get column span class
const getColSpanClass = (field: FieldConfig): string => {
    const span = field.colSpan ? Math.max(1, Math.min(12, field.colSpan)) : 12;
    return `col-span-12 md:col-span-${span}`; // Adicionado prefixo responsivo
};

// Function to handle updates from FormFieldWrapper
const handleFieldUpdate = (fieldName: string, newValue: any) => {
    console.log(`[Gatekeeper/DynamicForm] Field update "${fieldName}":`, newValue);
    setFieldValue(fieldName, newValue);
};

</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Iterate over rows -->
        <div
            v-for="(rowFields, rowIndex) in groupedFields"
            :key="`row-${rowIndex}`"
            class="grid grid-cols-12 gap-x-4 gap-y-4 items-start"
        >
            <!-- Iterate over fields in the current row -->
            <div
                v-for="field in rowFields"
                :key="field.name"
                :class="getColSpanClass(field)"
            >
                <FormFieldWrapper
                    :field="field"
                    :model-value="values[field.name]"
                    @update:model-value="handleFieldUpdate(field.name, $event)"
                >
                    <!-- FormFieldWrapper agora usa useField, não precisa mais passar o erro ou valor diretamente aqui -->
                    <!-- O slot default pode ser usado se necessário, mas geralmente não -->
                </FormFieldWrapper>
            </div>
        </div>

        <slot name="actions" :submit="onSubmit">
            <!-- Default actions can be placed here or provided by the parent -->
        </slot>
    </form>
</template> 