<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import Heading from '@/components/Heading.vue';
import { computed } from 'vue';

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

// Extrai o nome singular do recurso
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
                        <GtDataForm :fields="fields" :inertia-form="initialData"
                            :endpoint="route(`${routeNameBase}.store`)">
                            <template #actions="{ form }">
                                <div class="flex justify-end space-x-4">
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
                        </GtDataForm>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>