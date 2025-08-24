<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'; 
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    model: Record<string, any>; // Dados do modelo
    pageTitle: string;
    breadcrumbs: BreadcrumbItem[];
    routeNameBase: string; // Ex: 'admin.users'
}

const props = defineProps<Props>();
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <h3 class="mb-4 text-lg font-medium">Detalhes do Registro:</h3>
                    <pre class="whitespace-pre-wrap rounded bg-gray-100 p-4 text-sm dark:bg-gray-700">{{ JSON.stringify(model, null, 2) }}</pre>

                    <div class="mt-6 flex justify-end">
                         <Button
                            variant="outline"
                            :as="Link"
                            :href="route(`${routeNameBase}.index`)"
                         >
                             Voltar
                         </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 