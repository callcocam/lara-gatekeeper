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
    inputProps?: { placeholder?: string; [key: string]: any }; // Placeholder specifically
}>()

// Use defineModel for v-model binding (Vue 3.4+)
const model = defineModel<string | number | null>()

const options = computed(() => props.field.options || {});
const placeholder = computed(() => props.inputProps?.placeholder || 'Selecione...');

// Watch model changes for debugging
import { watch } from 'vue';
watch(model, (newValue) => {
  console.log(`[Gatekeeper/Select:${props.field.name}] Model updated:`, newValue);
});

</script>

<template>
    <Select v-model="model">
        <SelectTrigger :id="props.id" class="w-full" v-bind="props.inputProps">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent> 
            
            <SelectItem
                v-for="(label, value) in options"
                :key="value"
                :value="value.toString()"
            >
                {{ label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template> 