<script setup lang="ts">
import { computed, ref, watch } from 'vue'
// TODO: Assumir dependências como peer dependencies ou copiar/recriar
import { Checkbox } from 'shadcn-vue' 
import { Label } from 'shadcn-vue' 
import { cn } from '../../lib/utils' 

// Define props
const props = defineProps<{
    id: string;
    // modelValue: (string | number)[] | null | undefined; // Removido, usa defineModel
    field: {
        name: string; // Adicionado name
        options: Record<string, string>; // Required: { value: label }
        orientation?: 'vertical' | 'horizontal'; 
        [key: string]: any;
    };
    inputProps?: Record<string, any>; 
}>()

// Use defineModel, forçando a ser um array ou null
const model = defineModel<(string | number)[] | null>()

// Internal ref to always work with an array
const selectedValues = ref<(string | number)[]>(
    Array.isArray(model.value) ? [...model.value] : []
);

// Sync internal array with external model
watch(model, (newModelValue) => {
    const newArray = Array.isArray(newModelValue) ? [...newModelValue] : [];
    // Avoid unnecessary updates if arrays have same content (order doesn't matter for checkboxes)
    const sortedNew = [...newArray].sort();
    const sortedCurrent = [...selectedValues.value].sort();
    if (JSON.stringify(sortedNew) !== JSON.stringify(sortedCurrent)) {
         console.log(`[Gatekeeper/CheckboxList:${props.field.name}] Model changed externally, updating internal state.`);
         selectedValues.value = newArray;
    }
}, { deep: true });

// Sync internal changes back to the external model
watch(selectedValues, (newSelectedArray) => {
    // Sort to ensure consistent comparison/emission
    const sortedNewSelected = [...newSelectedArray].sort();
    const currentModelArray = Array.isArray(model.value) ? [...model.value].sort() : [];
    
    if (JSON.stringify(sortedNewSelected) !== JSON.stringify(currentModelArray)) {
        console.log(`[Gatekeeper/CheckboxList:${props.field.name}] Internal state changed, updating model.`);
        // Emit a new sorted array or null if empty
        model.value = sortedNewSelected.length > 0 ? sortedNewSelected : null; 
    }
}, { deep: true });

const options = computed(() => props.field.options || {});
const orientationClass = computed(() => {
    return props.field.orientation === 'horizontal' ? 'flex flex-wrap gap-x-4 gap-y-2' : 'space-y-2';
});

// Function to handle checkbox changes
function handleCheckboxChange(checked: boolean, value: string | number) {
    const stringValue = value.toString(); // Work with strings for includes check
    const currentValues = selectedValues.value.map(v => v.toString());
    let newSelectedValues: (string | number)[] = [];

    if (checked) {
        if (!currentValues.includes(stringValue)) {
            newSelectedValues = [...selectedValues.value, value]; // Add original value type
        }
    } else {
        newSelectedValues = selectedValues.value.filter(v => v.toString() !== stringValue);
    }
    // Update the internal ref, watcher will sync to model
    selectedValues.value = newSelectedValues;
}

</script>

<template>
    <div :id="props.id" :class="cn('py-1', orientationClass)">
        <div 
            v-for="(label, value) in options"
            :key="value"
            class="flex items-center space-x-2"
        >
            <Checkbox 
                :id="`${props.id}-${value}`" 
                 :checked="selectedValues.map(v => v.toString()).includes(value.toString())" // Compare as strings
                @update:checked="(checked) => typeof checked === 'boolean' && handleCheckboxChange(checked, value)" // Pass value too
                v-bind="props.inputProps"
                :value="value.toString()" // Pass value for accessibility/forms
            />
            <Label :for="`${props.id}-${value}`" class="font-normal cursor-pointer">
                {{ label }}
            </Label>
        </div>
    </div>
</template> 