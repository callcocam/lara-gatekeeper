<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <PlusCircleIcon class="mr-2 h-4 w-4" />
                {{ filter.label }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-2xl p-2" align="start">
            <ConfigurableForm :fields="filter.fields" :inertia-form="form" @update-field="updateFormField" />
            <Button variant="outline" size="sm" class="h-8 border-dashed" @click="clearFilters">Limpar</Button>
        </PopoverContent>
    </Popover>
</template>

<script lang="ts" setup>
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'
import { PlusCircleIcon } from 'lucide-vue-next'
import { useForm } from '@inertiajs/vue3'

interface Option {
    label: string;
    value: string | number;
    count?: number;
}

interface FilterProps {
    modelValue: any;
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option[];
        multiple?: boolean;
        fields: any[];
    };
}

const props = defineProps<FilterProps>();

 
const emit = defineEmits(['update:modelValue']);

const form = useForm({
});

const isOpen = ref(false); 
// Função para limpar todas as seleções
const clearFilters = () => {
    emit('update:modelValue', undefined);
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