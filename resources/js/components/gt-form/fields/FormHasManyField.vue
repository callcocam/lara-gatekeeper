<template>
    <div>
        <h1>HasManyField</h1>
    </div>
</template>

<script setup lang="ts">
import { FieldConfig } from '../../../types/field';


// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
  id: string;
  // modelValue: string | number | null; // Removido, usa defineModel
  field: FieldConfig // Adicionado name para logs
  inputProps?: Record<string, any>;
  error?: string;
}>()

const model = defineModel<string | number | null>()
const emit = defineEmits<{
  (e: 'reactive', value: string | number | null): void;
}>();

console.log('FormHasManyField', model.value);



</script>