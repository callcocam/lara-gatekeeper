<script setup lang="ts">
import { computed } from 'vue'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'

// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
    id: string;
    // modelValue: string | number | null; // Removido, usa defineModel
    field: {
        name: string; // Adicionado name
        options?: Record<string, string>; // Options for the select { value: label }
        [key: string]: any;
    };
    inputProps?: { placeholder?: string;[key: string]: any }; // Placeholder specifically
}>()

// Use defineModel for v-model binding (Vue 3.4+)
const model = defineModel<string | number | null>()

const options = computed(() => props.field.options || {});
const placeholder = computed(() => props.inputProps?.placeholder || 'Selecione...');
const emit = defineEmits<{
    (e: 'reactive', value: string | number | null): void;
}>();

// Watch model changes for debugging
import { watch } from 'vue';
import { Input } from '@/components/ui/input';
watch(model, (newValue: any) => {
    emit('reactive', newValue);
});

const computedOptions = computed(() => {
    const computedOptions: { value: string; label: string }[] = [];
    for (const key of Object.keys(options.value)) {
        if (key) {
            computedOptions.push({ value: key.toString(), label: options.value[key] });
        }
    } 
    return computedOptions;
});
</script>

<template>
    <div>
        <Select v-model="model" v-if="computedOptions.length > 0">
            <SelectTrigger :id="props.id" class="w-full" v-bind="props.inputProps">
                <SelectValue :placeholder="placeholder" />
            </SelectTrigger>
            <SelectContent>
                <template v-if="computedOptions.length > 0">
                    <SelectItem v-for="item in computedOptions" :key="item.value" :value="item.value.toString()">
                        {{ item.label }}
                    </SelectItem>
                </template>
            </SelectContent>
        </Select>
        <Input readonly class="text-sm text-gray-500" v-else :placeholder="'Nenhum item encontrado'" />
    </div>
</template>