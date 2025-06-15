<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'

const props = defineProps<{
  id: string;
  field: {
    name: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    [key: string]: any;
  };
  inputProps?: Record<string, any>;
}>()

const model = defineModel<boolean | null>()

const labelText = computed(() => props.field.label || props.field.name)

// Computed para lidar com valores null/undefined para o checkbox
const modelValueForCheckbox = computed({
  get: () => model.value ?? false,
  set: (value) => {
    console.log(`[Gatekeeper/Checkbox:${props.field.name}] Setting value:`, value);
    model.value = value;
  }
});
</script>

<template>
  <div :id="props.id" class="flex items-center gap-3 py-1">
    <Checkbox
      :id="props.id + '-checkbox'"
      v-model:modelValue="modelValueForCheckbox"
      :disabled="props.field.disabled"
      v-bind="props.inputProps"
    />
    <Label :for="props.id + '-checkbox'" class="font-normal cursor-pointer select-none">
      {{ labelText }}
      <span v-if="props.field.required" class="text-red-500 ml-1">*</span>
    </Label>
    <span v-if="props.field.description" class="text-xs text-gray-500 ml-2">{{ props.field.description }}</span>
  </div>
</template> 