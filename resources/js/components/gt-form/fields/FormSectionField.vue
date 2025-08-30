<template>
  <fieldset class="mercadologico-selector border border-input rounded-md px-4 pb-2 dark:border-gray-700 relative">
    <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">
      {{ field.label }}
    </legend>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" v-if="field.description">{{ field.description }}</p>
    <div v-if="fields" class="grid grid-cols-12 gap-4">
      <div v-for="fieldOther in fields" :key="fieldOther.name" :class="getColSpanClass(fieldOther)">
        <GtFormFieldWrapper :field="fieldOther" :model-value="safeModel[fieldOther.name]"
          :error="formData.errors[fieldOther.name]" @update:model-value="handleFieldUpdate(fieldOther.name, $event)"
          @fieldAction="handleFieldAction" />
      </div>
    </div>
    </fieldset>
</template>

<script setup lang="ts">
import { FieldConfig } from '../../../types/field';
import { computed, ref } from 'vue'; 
import GtFormFieldWrapper from '../GtFormFieldWrapper.vue';  
import { useForm } from '@inertiajs/vue3';

// Define props expected from FormFieldWrapper (agora dentro do pacote)
const props = defineProps<{
  id: string;
  // modelValue: string | number | null; // Removido, usa defineModel
  field: FieldConfig // Adicionado name para logs
  inputProps?: Record<string, any>;
  error?: string;
  modelValue: any;
}>()

// Formulário para gerenciar dados e erros
const formData = useForm({
  ...props.modelValue,
  errors: {},
});

// Modelo seguro para evitar mutações diretas das props
const safeModel = ref({
  ...props.modelValue,
});

const model = defineModel<any>()
const emit = defineEmits<{
  (e: 'reactive', value: string | number | null): void;
  (e: 'fieldAction', action: string, data: any): void;
}>();

console.log('FormSectionField', model.value);



const fields = computed(() => props.field.fields);

const getColSpanClass = (field: FieldConfig) => {
  return `col-span-${field.colSpan}`;
};



const handleFieldUpdate = (fieldName: string, value: any) => {
  emit('reactive', value);
};

const handleFieldAction = (action: string, data: any) => {
  emit('fieldAction', action, data);
};



</script>