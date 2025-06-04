<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import Heading from '@/components/Heading.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';

// --- Tipos ---
interface FieldConfig {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    colSpan?: number;
    options?: { value: string | number; label: string }[];
    [key: string]: any;
}

interface Props {
    fields: FieldConfig[];
    initialValues: Record<string, any>;
    modelId: number | string;
    pageTitle: string;
    pageDescription?: string;
    breadcrumbs: BreadcrumbItem[];
    routeNameBase: string;
}

const props = defineProps<Props>();
const page = usePage();

// --- Preparar dados iniciais para useForm ---
const initialData = computed(() => {
    const data: Record<string, any> = {};
    if (Array.isArray(props.fields)) {
        props.fields.forEach(field => {
            data[field.name] = props.initialValues?.[field.name] ?? null;
        });
    }
    return data;
});

// --- Inicializar useForm do Inertia ---
const form = useForm({
    ...initialData.value,
    _method: 'PUT',
});

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

// Extrai o nome singular do recurso
const resourceSingularName = props.routeNameBase.split('.').pop()?.replace(/s$/, '') ?? 'registro';
const resourceSingularTitle = resourceSingularName.charAt(0).toUpperCase() + resourceSingularName.slice(1); 

// --- Método para atualizar campo do form via evento ---
const updateFormField = (fieldName: string, newValue: any) => {
    // Cast form to any for this assignment
    (form as any)[fieldName] = newValue;
};

// --- Função de Submissão ---
const submitForm = () => { 
    // Usar form.put para update

    form.post(route(`${props.routeNameBase}.update`, props.modelId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- <Breadcrumbs :breadcrumbs="breadcrumbs" /> -->

      <div class="w-full max-w-5xl mx-auto mt-6 flex flex-col">
        <Heading :title="pageTitle" :description="pageDescription ?? ''" class="mb-6" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <DynamicForm
                        :fields="fields"
                        :inertia-form="form"
                        @submit.prevent="submitForm"
                        @updateField="updateFormField"
                    >
                        <template #actions>
                            <div class="flex justify-end space-x-4">
                                <Button type="button" variant="outline" 
                                        @click="() => router.get(route(`${routeNameBase}.index`))" 
                                        :disabled="form.processing">
                                    Cancelar
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Atualizando...' : `Atualizar ${resourceSingularTitle}` }}
                                </Button>
                            </div>
                        </template>
                    </DynamicForm>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template> 