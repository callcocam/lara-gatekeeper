<script setup lang="ts">
import { computed } from 'vue'
import { Textarea } from '@/components/ui/textarea'
// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
  id: string;
  // modelValue: string | null; // Removido, usa defineModel
  field: { name: string;[key: string]: any }; // Adicionado name
  inputProps?: Record<string, any>;
  error?: string | null | undefined;
}>()

// Use defineModel for v-model binding (Vue 3.4+)
const model = defineModel<string | null>()
const emit = defineEmits<{
  (e: 'reactive', value: string | null): void;
}>();

// Computed property to handle null/undefined for textarea binding
const modelValueForTextarea = computed({
  get: () => model.value ?? '',
  set: (value) => {
    model.value = value === '' ? null : value;
    emit('reactive', model.value);
  }
});

</script>

<template>
  <div class="space-y-2">
    <GtLabel :field="field" :error="error" :fieldId="props.id" />
    <Textarea :id="props.id" v-model="modelValueForTextarea" v-bind="props.inputProps" />
    <GtDescription :description="field.description" :error="props.error" />
    <GtError :id="props.id" :error="props.error" />
  </div>
</template>