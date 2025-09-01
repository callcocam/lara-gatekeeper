<template>
    <Tabs :default-value="activeTab" class="w-full">
        <TabsList class="w-full">
            <TabsTrigger v-for="tab in tabs" :key="tab.name" :value="tab.name" @click="tabUrl(tab)">
                {{ tab.label }}
            </TabsTrigger>
        </TabsList>
        <TabsContent v-for="tab in tabs" :key="tab.name" :value="tab.name" as="div">
            <div v-if="tab.fields" class="grid grid-cols-12 gap-4">
                <div v-for="fieldOther in tab.fields" :key="fieldOther.name" :class="getColSpanClass(fieldOther)">
                    <GtFormFieldWrapper :field="fieldOther" :model-value="modelValue?.[fieldOther.name] ?? null"
                        :error="errors?.[fieldOther.name] ?? ''"
                        @update:model-value="handleFieldUpdate(fieldOther.name, $event)" @fieldAction="handleFieldAction" />
                </div>
            </div>
        </TabsContent>
    </Tabs>
</template>

<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { router } from '@inertiajs/vue3';
import { FieldConfig, TabConfig } from '../../types/field';
import GtFormFieldWrapper from './GtFormFieldWrapper.vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    activeTab: string;
    tabs: TabConfig[];
    errors: Record<string, string | null> | null | undefined;
}>();

const modelValue = defineModel<Record<string, string | number | null>>('modelValue');


// Parâmetros da query string atual (não utilizado atualmente)
const queryParams = ref(Object.fromEntries(new URLSearchParams(window.location.search).entries()));

const tabUrl = (tab: TabConfig) => {
    router.get(window.location.pathname, {
        tab: tab.name,
        ...queryParams.value
    }, {
        preserveScroll: true,
        preserveState: true
    })
}


const getColSpanClass = (field: FieldConfig) => {
    return `col-span-${field.colSpan}`;
};

const handleFieldUpdate = (name: string, value: string | number | null) => {
    const model = modelValue.value;
    if (model) {
        model[name] = value;
    }
    modelValue.value = model;
};

const handleFieldAction = (name: string, action: string) => {

};


</script>