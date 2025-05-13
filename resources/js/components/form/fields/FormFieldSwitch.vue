<script setup lang="ts">
import { computed } from 'vue'
import { Switch } from '@/components/ui/switch'
import { Label } from '@/components/ui/label'
import { cn } from '../../../lib/utils'

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
</script>

<template>
  <div :id="props.id" class="flex items-center gap-3 py-1">
    <Switch
      :id="props.id + '-switch'"
      v-model="model"
      :disabled="props.field.disabled"
      v-bind="props.inputProps"
    />
    <Label :for="props.id + '-switch'" class="font-normal cursor-pointer select-none">
      {{ labelText }}
      <span v-if="props.field.required" class="text-red-500 ml-1">*</span>
    </Label>
    <span v-if="props.field.description" class="text-xs text-gray-500 ml-2">{{ props.field.description }}</span>
  </div>
</template> 