<template>
    <div class="flex items-center justify-between p-4 border-b">
        <div class="flex items-center space-x-2">
            <FilterRenderer v-for="filter in filters" :key="filter.id" :filter="filter"
                @update:modelValue="(payload) => updateFilterValue(payload)" :query-params="queryParams" />
        </div>
        <div class="relative">
            <Input placeholder="Pesquisar..." v-model="searchQuery" v-if="searchableColumns.length" class="mr-9" />
            <Search class="absolute right-2 top-1/2 transform -translate-y-1/2 w-4 h-4" />
        </div>
    </div>
</template>
<script lang="ts" setup>
import { ref, toRefs, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';
import FilterRenderer from './FilterRenderer.vue';
interface Props {
    filters: Record<string, any>;
    queryParams: Record<string, any>;
    searchableColumns: string[];
}
const props = defineProps<Props>();

const { filters, queryParams, searchableColumns } = toRefs(props);

const searchQuery = ref(queryParams.value.search || '');

watch(searchQuery, (newValue) => {
    queryParams.value.search = newValue;
    updateFilters();
});

const updateFilterValue = (payload: { name: string; value: string | number }) => {
    queryParams.value[payload.name] = payload.value;
    updateFilters();
};

const updateFilters = () => {
    const url = new URL(window.location.href, window.location.origin);

    if (Object.keys(queryParams.value).length) {
        Object.entries(queryParams.value).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                // Se é array, converte para string separada por vírgulas
                if (Array.isArray(value)) {
                    if (value.length > 0) {
                        url.searchParams.set(key, value.join(','));
                    } else {
                        url.searchParams.delete(key);
                    }
                } else {
                    url.searchParams.set(key, String(value));
                }
            } else {
                url.searchParams.delete(key);
            }
        });
    }

    router.visit(url.toString(), { preserveState: true, replace: true });
};

</script>