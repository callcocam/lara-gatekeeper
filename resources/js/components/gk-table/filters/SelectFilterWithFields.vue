<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <PlusCircleIcon class="mr-2 h-4 w-4" />
                <span v-if="getLabel()">{{ getLabel() }}</span>
                <span v-else>{{ filter.label }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-2xl p-4 flex flex-col gap-2" align="start">
            <ConfigurableForm :fields="filter.fields" :inertia-form="form" @update-field="updateFormField" />
            <Button variant="outline" size="sm" class="h-8 border-dashed cursor-pointer"
                @click="clearFilters">Limpar</Button>
        </PopoverContent>
    </Popover>
</template>

<script lang="ts" setup>
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { computed, onMounted, ref } from 'vue'
import { PlusCircleIcon } from 'lucide-vue-next'
import { useForm } from '@inertiajs/vue3'

interface Option {
    label: string;
    value: string | number;
    count?: number;
}

interface FilterProps {
    modelValue: any;
    queryParams: Record<string, any>;
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option[];
        multiple?: boolean;
        fields: any[];
        value: any;
    };
}

const props = defineProps<FilterProps>();


const emit = defineEmits(['update:modelValue', 'reset']);

const initialData = computed(() => {
    const data: Record<string, any> = {};
    if (Array.isArray(props.filter.fields)) {
        props.filter.fields.forEach((field: any) => {
            data[field.name] = props.queryParams[field.name] || null;
        });
    }
    return data;
});

const getLabel = () => {
    const label = props.filter.fields.map((field: any) => {
        const value = props.queryParams[field.name] || null;
        if (value) {
            return field.options[value] || null;
        }
        return null;
    }).filter(Boolean);
    return label.join(' / ');
}

const form = useForm({
    ...initialData.value,
});

const isOpen = ref(false);

const names = ref<string[]>([]);

onMounted(() => {
    names.value = props.filter.fields.map((field: any) => field.name);
});
// Função para limpar todas as seleções
const clearFilters = () => {
    emit('reset', names.value);
    form.reset();
};

// --- Método para atualizar campo do form via evento ---
const updateFormField = (fieldName: string, newValue: any) => {
    // Cast form to any for this assignment
    (form as any)[fieldName] = newValue;
    emit('update:modelValue', {
        name: fieldName,
        value: newValue
    });
};
</script>