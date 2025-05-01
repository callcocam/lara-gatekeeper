<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useForm } from 'vee-validate'
// @ts-ignore
import FormFieldWrapper from '@/components/form/FormFieldWrapper.vue'
// DO NOT import types from self
// import type { FieldConfig, FormValues, FormErrors } from '@/components/DynamicForm.vue'; 

// DEFINE and EXPORT types locally
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
    [key: string]: string[];
}

export interface FormValues { 
    [key: string]: any;
}

const props = defineProps<{
    fields: FieldConfig[]; // Use the locally defined type
    initialValues?: FormValues; // Use the locally defined type
    errors?: FormErrors; // Use the locally defined type
}>()

const emit = defineEmits<{ (e: 'submit', values: FormValues): void }>()

// Use VeeValidate's useForm, destructure setFieldValue
const { values, setValues, handleSubmit, setFieldError, errors: formErrors, setFieldValue } = useForm<FormValues>({
    initialValues: props.initialValues || {},
});

// *** DEBUG: Watch for changes in the main values object ***
watch(values, (newValues) => {
    console.log('[DynamicForm] values updated:', JSON.stringify(newValues));
}, { deep: true });

// Watch for external errors and update VeeValidate form errors
watch(() => props.errors, (newErrors) => {
    if (newErrors) {
        console.log('[DynamicForm] Received backend errors:', JSON.stringify(newErrors)); // Log received errors
        Object.keys(newErrors).forEach(field => {
            if (newErrors[field] && newErrors[field].length > 0) {
                const errorMessage = newErrors[field][0];
                console.log(`[DynamicForm] Setting error for field "${field}":`, errorMessage); // Log before setting
                setFieldError(field, errorMessage); 
            }
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
    props.fields.forEach(field => {
        const row = field.row || 0; // Default to row 0 if not specified
        if (!groups[row]) {
            groups[row] = [];
        }
        groups[row].push(field);
    });
    // Sort rows numerically
    return Object.entries(groups)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([_, fieldsInRow]) => fieldsInRow);
});

// Function to get column span class
const getColSpanClass = (field: FieldConfig): string => {
    const span = field.colSpan ? Math.max(1, Math.min(12, field.colSpan)) : 12;
    return `col-span-${span}`;
};

// Function to handle updates from FormFieldWrapper
const handleFieldUpdate = (fieldName: string, newValue: any) => {
    setFieldValue(fieldName, newValue);
};

</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Iterate over rows --> 
        <div 
            v-for="(rowFields, rowIndex) in groupedFields"
            :key="`row-${rowIndex}`"
            class="grid grid-cols-12 gap-x-4 gap-y-4" 
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
                </FormFieldWrapper>
            </div>
        </div>

        <slot name="actions" :submit="onSubmit">
            <!-- Default actions can be placed here or provided by the parent -->
            <!-- Example:
            <div class="flex justify-end space-x-4 mt-6" >
                <button type="button" class="px-4 py-2 border rounded-md ...">Cancelar</button>
                <button type="submit" class="px-4 py-2 border rounded-md ...">Salvar</button>
            </div>
            -->
        </slot>
    </form>
</template> 