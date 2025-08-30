<template>
  <fieldset class="mercadologico-selector border border-input rounded-md px-4 pb-2 dark:border-gray-700 relative">
    <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">
      {{ field.label }}
    </legend>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" v-if="field.description">{{ field.description }}</p>
    <div v-if="field.fields" class="grid grid-cols-12 gap-4">
      <div v-for="fieldOther in fields" :key="fieldOther.name" :class="getColSpanClass(fieldOther)">
        <GtFormFieldWrapper :field="fieldOther" :model-value="formData[fieldOther.name]"
          :error="formData.errors[fieldOther.name]" @update:model-value="handleFieldUpdate(fieldOther.name, $event)"
          @fieldAction="handleFieldAction" />
      </div>
    </div>
  </fieldset>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue';
import { FieldConfig } from '../../../types/field';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
  field: FieldConfig & {
    cascadingFields: string[];
  };
  error?: string;
  modelValue: any;
}>()

const queryParams = ref(Object.fromEntries(new URLSearchParams(window.location.search).entries()));

const formData = useForm({
  ...props.modelValue,
  ...queryParams.value,
  errors: {},
});

const selfModel = ref({
  ...props.modelValue,
  ...queryParams.value,
});
const emit = defineEmits<{
  (e: 'update:model-value', value: any): void;
  (e: 'fieldAction', action: string, data: any): void;
}>();


const fields = computed(() => props.field.fields);

const handleFieldUpdate = (fieldName: string, value: any) => {
  console.log('=== LÓGICA DE CASCATA ATIVADA ===');
  console.log(fieldName, value, 'fieldName, value');
  
  // Atualiza o modelo local
  selfModel.value[fieldName] = value;
  
  // Define a ordem hierárquica dos campos
  const fieldOrder = ['segmento_varejista', 'departamento', 'subdepartamento', 'categoria', 'subcategoria', 'subsegmento'];
  
  // Encontra a posição do campo atualizado
  const currentIndex = fieldOrder.indexOf(fieldName);
  console.log('Campo atualizado:', fieldName, 'posição:', currentIndex);
  
  // Mantém apenas os campos até o atual, remove os posteriores
  const fieldsToKeep = fieldOrder.slice(0, currentIndex + 1);
  console.log('Campos a manter:', fieldsToKeep);
  
  const url = new URL(window.location.href, window.location.origin);
  url.search = '';
  
  // Adiciona apenas os campos que devem ser mantidos
  fieldsToKeep.forEach(fieldName => {
    if (selfModel.value[fieldName]) {
      console.log(`Mantendo ${fieldName}=${selfModel.value[fieldName]} na URL`);
      url.searchParams.set(fieldName, String(selfModel.value[fieldName]));
    }
  });
  
  // Remove campos posteriores do modelo local
  const fieldsToRemove = fieldOrder.slice(currentIndex + 1);
  fieldsToRemove.forEach(fieldName => {
    if (selfModel.value[fieldName]) {
      console.log(`Removendo ${fieldName} do modelo (campo posterior)`);
      delete selfModel.value[fieldName];
    }
  });
  
  console.log('URL final:', url.toString());
  console.log('Modelo atualizado:', selfModel.value);
  console.log('=== FIM DA LÓGICA DE CASCATA ===');
  
  router.visit(url.toString(), { replace: true });
};

const handleFieldAction = (action: string, data: any) => {
  emit('fieldAction', action, data);
};

const getColSpanClass = (field: FieldConfig) => {
  return `col-span-${field.colSpan}`;
};
</script>

?