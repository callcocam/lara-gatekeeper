<template>
    <Select v-model="selectedValue">
        <SelectTrigger>
            <SelectValue placeholder="Selecione uma opção" />
        </SelectTrigger>
        <SelectContent class="w-full">
            <SelectItem v-for="option in filter.options" :key="option.value" :value="option.value">
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>

<script lang="ts" setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, watch } from 'vue';

interface Option {
    label: string;
    value: string | number;
}

interface FilterProps {
    modelValue: string | number;
    filter: {
        id: string;
        component: any;
        options: Option[];
    };
}

const props = defineProps<FilterProps>();

const emit = defineEmits(['update:modelValue']);

const selectedValue = ref(props.modelValue);

watch(selectedValue, (newValue) => {
    // Emitir evento de atualização para o componente pai
    emit('update:modelValue', newValue);
});

</script>