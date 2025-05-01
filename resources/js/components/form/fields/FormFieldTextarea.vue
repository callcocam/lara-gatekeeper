<script setup lang="ts">
import { computed } from 'vue' 
// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
    id: string;
    // modelValue: string | null; // Removido, usa defineModel
    field: { name: string; [key: string]: any }; // Adicionado name
    inputProps?: Record<string, any>; 
}>()

// Use defineModel for v-model binding (Vue 3.4+)
const model = defineModel<string | null>()

// Computed property to handle null/undefined for textarea binding
const modelValueForTextarea = computed({
  get: () => model.value ?? '',
  set: (value) => { 
    console.log(`[Gatekeeper/Textarea:${props.field.name}] Setting value:`, value);
    model.value = value === '' ? null : value; 
  }
});

</script>

<template>
    <Textarea
        :id="props.id"
        v-model="modelValueForTextarea"
        v-bind="props.inputProps"
    />
</template> 