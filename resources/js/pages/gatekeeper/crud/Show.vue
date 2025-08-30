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
    fullWidth?: boolean;
    actions?: any[];
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
    data._method = 'PUT';
    return data;
}); 

</script>

<template>

    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- <Breadcrumbs :breadcrumbs="breadcrumbs" /> -->

        <div class="p-12">
            <div class="w-full mx-auto mt-2 flex flex-col"
                :class="{ 'max-w-full': fullWidth, 'max-w-7xl': !fullWidth }">
                <GtHeading :title="pageTitle" :description="pageDescription ?? ''" class="mb-6">
                    <ActionRenderer v-for="action in actions" :key="action.id" :action="action" position="top" />
                </GtHeading>
                <div class="w-full sm:px-6 lg:px-8">
                    <div class="overflow-hidden bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <pre>{{ JSON.stringify(initialValues, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>