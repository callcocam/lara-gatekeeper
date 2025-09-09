<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { computed } from 'vue';
import { ActionItemProps } from '../../../types/field';

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
    pageTitle: string;
    pageDescription?: string;
    breadcrumbs: BreadcrumbItem[];
    routeNameBase: string;
    actions?: ActionItemProps[];
    fullWidth?: boolean;
    routes?: {
        index: string;
        show: string;
        edit: string;
        update: string;
        destroy: string;
        store: string;
    };
    resourceSingularTitle?: string;
    resourcePluralTitle?: string;
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


</script>

<template>

    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- <Breadcrumbs :breadcrumbs="breadcrumbs" /> -->

        <div class="w-full max-w-5xl mx-auto mt-6 flex flex-col">
            <GtHeading :title="pageTitle" :description="pageDescription ?? ''" class="mb-6">
                <ActionRenderer v-for="action in actions" :key="action.id" :action="action" position="top" />
            </GtHeading>
            <div class="py-12">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="overflow-hidden bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <GtDataForm :fields="fields" :inertia-form="initialData"
                            :endpoint="routes?.store ?? '#'">
                            <template #actions="{ form }">
                                <div class="flex justify-end space-x-4">
                                    <Button type="button" variant="outline"
                                        @click="() => router.get(routes?.index ?? '#')"
                                        :disabled="form.processing">
                                        Cancelar
                                    </Button>
                                    <Button type="submit" :disabled="form.processing">
                                        {{ form.processing ? 'Salvando...' : `Salvar` }}
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