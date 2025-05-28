<script setup lang="ts">
import { computed, watch } from 'vue' 
import { cn } from '../../../lib/utils' 
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Label } from '@/components/ui/label'

// Define props
const props = defineProps<{
    id: string;
    // modelValue: string | number | null; // Removido, usa defineModel
    field: {
        name: string; // Adicionado name
        options: Record<string, string>; // Required: { value: label }
        orientation?: 'vertical' | 'horizontal';
        [key: string]: any;
    };
    inputProps?: Record<string, any>; 
}>()

// Use defineModel for v-model binding
const model = defineModel<string | number | null>()

const options = computed(() => props.field.options || {});
const orientationClass = computed(() => {
    return props.field.orientation === 'horizontal' ? 'flex flex-wrap gap-x-4 gap-y-2' : 'space-y-2'; // Melhorado wrap
});

// Watch model changes for debugging
watch(model, (newValue) => {
  console.log(`[Gatekeeper/RadioGroup:${props.field.name}] Model updated:`, newValue);
});

</script>

<template>
    <RadioGroup 
        :id="props.id" 
        :model-value="model?.toString()"  
        @update:model-value="(val: any) => model = val" 
        :class="cn('py-1', orientationClass)"
        v-bind="props.inputProps"
    >
        <div 
            v-for="(label, value) in options"
            :key="value"
            class="flex items-center space-x-2"
        >
            <RadioGroupItem :id="`${props.id}-${value}`" :value="value.toString()" />
            <Label :for="`${props.id}-${value}`" class="font-normal cursor-pointer">
                {{ label }}
            </Label>
        </div>
    </RadioGroup>
</template> 