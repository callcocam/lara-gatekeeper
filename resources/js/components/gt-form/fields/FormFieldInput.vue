<script setup lang="ts">
import { computed } from 'vue'
// TODO: Assumir Input como peer dependency de shadcn-vue ou copiar/recriar 
import { Input } from '@/components/ui/input'
import GtLabel from '../GtLabel.vue';
import { FieldConfig } from '../../../types/field';
// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
  id: string;
  // modelValue: string | number | null; // Removido, usa defineModel
  field: FieldConfig // Adicionado name para logs
  inputProps?: Record<string, any>;
  error?: string;
}>()

// Use defineModel for v-model binding (Vue 3.4+)
const model = defineModel<string | number | null>()
const emit = defineEmits<{
  (e: 'reactive', value: string | number | null): void;
}>();
// Computed property to handle null/undefined for input binding
// Needed because native <input> doesn't handle null well
const modelValueForInput = computed({
  get: () => model.value ?? '',
  set: (value) => {
    // console.log(`[Gatekeeper/Input:${props.field.name}] Setting value:`, value);
    model.value = value === '' ? null : value;
    emit('reactive', model.value);
  }
});

// Determine the input type based on field config, default to 'text'
const inputType = computed(() => {
  const type = props.field.type;
  if (['text', 'email', 'password', 'number', 'url', 'tel', 'search'].includes(type)) {
    return type;
  }
  return 'text';
});

</script>

<template>
  <div class="space-y-2">
    <GtLabel :field="field" :error="error" :fieldId="props.id" />
    <Input :id="props.id" :type="inputType" v-model="modelValueForInput" v-bind="field.inputProps" autocomplete="off" />
    <GtDescription :description="field.description" :error="props.error" />
    <GtError :id="props.id" :error="props.error" />
  </div>
</template>