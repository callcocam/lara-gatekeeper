<template>
    <div class="space-y-4">
        <form class="space-y-4" @submit.prevent="submitForm" :data-form="formData">
            <div class="grid grid-cols-12 gap-x-4 gap-y-4 items-start">
                <div v-for="field in fields" :key="field.name" :class="getColSpanClass(field)">
                    <template v-if="field.type === 'tabs'">
                        <GtFormFieldTabs :activeTab="field.activeTab" :tabs="field.tabs" :model-value="formData[field.name]"
                            :errors="formData.errors[field.name]"
                            @update:model-value="handleFieldUpdate(field.name, $event)"
                            @fieldAction="handleFieldAction" />
                    </template>
                    <template v-else>
                        <GtFormFieldWrapper :field="field" :model-value="formData[field.name]"
                            :error="formData.errors[field.name]"
                            @update:model-value="handleFieldUpdate(field.name, $event)"
                            @fieldAction="handleFieldAction" />
                    </template>
                </div>
                <div class="col-span-full">
                    <slot name="actions" :form="formData"></slot>
                </div>
            </div>
        </form>
    </div>
</template>
<script setup lang="ts">
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import { useForm, usePage, type InertiaForm } from '@inertiajs/vue3' // Tipo correto
import GtFormFieldWrapper from './GtFormFieldWrapper.vue';
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
    endpoint: string;
    fields: FieldConfig[];
    inertiaForm: InertiaForm<Record<string, any>>; // Receber o form do Inertia
}>()

const emit = defineEmits<{
    (e: 'update:data', value: FormValues): void;
    (e: 'submit'): void;
    (e: 'error', errors: FormErrors): void;
    (e: 'success'): void;
}>()

const page = usePage();
const formData = useForm({ ...props.inertiaForm })


// --- Observador de Mensagens Flash ---
watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
}, { deep: true });

// --- Observador de Erros de Validação (para erro genérico) ---
watch(() => page.props.errors, (errors: any) => {
    if (errors && Object.keys(errors).length > 0 && !page.props.flash) {
        toast.error('Por favor, verifique os erros no formulário.');
    }
}, { deep: true });

const handleFieldUpdate = (fieldName: string, newValue: any) => {
    formData[fieldName] = newValue;
    emit('update:data', formData.value);
}

const handleFieldAction = (action: string, data: any, fieldName: string) => {
    console.log('Field action:', action, data, fieldName);
}

// --- Função de Submissão ---
const submitForm = () => {
    // Usar form.put para update
    formData.post(props.endpoint, {
        preserveScroll: true,
        onBefore: () => {
            // Isso é uma função PHP opcional que você deve criar em Laravel
            // Ela será chamada antes da submissão do formulário
            // Verifique se a função existe no controller e se retorna um JSON válido
            console.log('onBefore');
        },
        onSuccess: () => {
            // Isso é uma função PHP opcional que você deve criar em Laravel
            // Ela será chamada após a submissão do formulário
            // Verifique se a função existe no controller e se retorna um JSON válido
            console.log('onSuccess');
            emit('success');
        },
        onError: (errors: FormErrors) => {
            emit('error', errors);
        },
        onFinish: () => {
            console.log('onFinish');
            emit('submit');
        },
    });
};



// Manter getColSpanClass
const getColSpanClass = (field: FieldConfig): string => {
    const span = field.colSpan ? Math.max(1, Math.min(12, field.colSpan)) : 12;
    return `col-span-12 md:col-span-${span}`;
};
</script>