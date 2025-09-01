<template>
    <Tabs :default-value="field.activeTab ?? tabs[0].name" class="w-full">
        <TabsList class="w-full">
            <TabsTrigger v-for="tab in tabs" :key="tab.name" :value="tab.name" @click="tabUrl(tab)">
                {{ tab.label }}
            </TabsTrigger>
        </TabsList>
        <TabsContent v-for="tab in tabs" :key="tab.name" :value="tab.name" as="div">
            <div v-if="tab.fields" class="grid grid-cols-12 gap-4">
                <div v-for="fieldOther in tab.fields" :key="fieldOther.name" :class="getColSpanClass(fieldOther)">
                    <GtFormFieldWrapper :field="fieldOther" :model-value="safeModel[fieldOther.name]"
                        :error="error ? error[fieldOther.name] : null"
                        @update:model-value="handleFieldUpdate(fieldOther.name, $event)"
                        @fieldAction="handleFieldAction" />
                </div>
            </div>
        </TabsContent>
    </Tabs>
</template>

<script setup lang="ts">
import { FieldConfig, TabConfig } from '../../../types/field';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';


// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
    id: string;
    // modelValue: string | number | null; // Removido, usa defineModel
    field: FieldConfig & { tabs: TabConfig[], activeTab: string | null } // Adicionado name para logs
    inputProps?: Record<string, any>;
    error?: Record<string, string>;
    modelValue: Record<string, string | number | null>;
}>()

const emit = defineEmits<{
    (e: 'reactive', value: Record<string, string | number | null>): void;
}>();


const safeModel = ref(props.modelValue)

const handleFieldUpdate = (name: string, value: string | number | null) => {
   safeModel.value[name] = value;
   emit('reactive', safeModel.value);
}

const handleFieldAction = (action: string) => {
    console.log(action);
}

const tabs = computed(() => {
    return props.field.tabs;
}) 

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
}



</script>