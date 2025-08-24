<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import Heading from '@/components/Heading.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';

// --- Tipos --- (Manter localmente ou importar)
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
    initialValues: Record<string, any>; // Deve ser um objeto com chaves definidas, mesmo que vazias
    pageTitle: string;
    pageDescription?: string;
    breadcrumbs: BreadcrumbItem[];
    routeNameBase: string; // Ex: 'admin.users'
}

const props = defineProps<Props>();
const page = usePage(); // Manter usePage para flash

// --- Preparar dados iniciais para useForm ---
// Garante que todas as chaves dos campos existam no objeto inicial
const initialData = computed(() => {
    const data: Record<string, any> = {};
    if (Array.isArray(props.fields)) {
        props.fields.forEach(field => {
            data[field.name] = props.initialValues?.[field.name] ?? null; // Usa valor inicial ou null
        });
    }
    return data;
});

// --- Inicializar useForm do Inertia ---
const form = useForm(initialData.value);

// --- Observador de Mensagens Flash (Manter) ---
watch(() => page.props.flash, (flash: any) => {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
}, { deep: true });

// --- Método para atualizar campo do form via evento ---
const updateFormField = (fieldName: string, newValue: any) => {
    // Cast form to any for this assignment
    (form as any)[fieldName] = newValue;
};

// --- Função de Submissão --- 
const submitForm = () => {
    form.post(route(`${props.routeNameBase}.store`), {
        preserveScroll: true,
        // onSuccess e onError são tratados automaticamente pelo useForm
        // (preenche form.errors, mostra flash, etc.)
    });
};

// --- Nome do Recurso (Manter) ---
const resourceSingularName = props.routeNameBase.split('.').pop()?.replace(/s$/, '') ?? 'registro';
const resourceSingularTitle = resourceSingularName.charAt(0).toUpperCase() + resourceSingularName.slice(1);

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
                        <!-- Remover console.log(errors) -->
                        <!-- Passar o objeto form e fields para DynamicForm -->
                        <DynamicForm 
                            :fields="fields" 
                            :inertia-form="form" 
                            @submit.prevent="submitForm"
                            @updateField="updateFormField"
                         >
                            <template #actions>
                                <div class="flex justify-end space-x-4">
                                    <!-- Cancelar usa router importado -->
                                    <Button type="button" variant="outline"
                                        @click="() => router.get(route(`${routeNameBase}.index`))" 
                                        :disabled="form.processing">
                                        Cancelar
                                    </Button>
                                    <Button type="submit" :disabled="form.processing">
                                        {{ form.processing ? 'Salvando...' : `Salvar ${resourceSingularTitle}` }}
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