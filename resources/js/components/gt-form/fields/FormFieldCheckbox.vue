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
  error?: string | null | undefined;
}>()

const model = defineModel<boolean | null>()

const labelText = computed(() => props.field.label || props.field.name)

const emit = defineEmits<{
  (e: 'reactive', value: boolean | null): void;
}>();

// Função para lidar com mudanças do checkbox
const handleChange = (checked: boolean | 'indeterminate') => {
  const value = checked === 'indeterminate' ? false : Boolean(checked);
  model.value = value;
  emit('reactive', value);
};
</script>

<template>
  <div class="space-y-2">
    <GtLabel :field="field" :error="error" :fieldId="props.id" />
    <div :id="props.id" class="flex items-center gap-3 py-1">
      <Checkbox :id="props.id + '-checkbox'" :checked="Boolean(model)" @update:model-value="handleChange"
        :disabled="props.field.disabled" v-bind="props.inputProps" />
      <Label :for="props.id + '-checkbox'" class="font-normal cursor-pointer select-none">
        {{ labelText }}
        <span v-if="props.field.required" class="text-red-500 ml-1">*</span>
      </Label>
      <GtDescription :description="field.description" :error="props.error" />
      <GtError :id="props.id" :error="props.error" />
    </div>
  </div>
</template>