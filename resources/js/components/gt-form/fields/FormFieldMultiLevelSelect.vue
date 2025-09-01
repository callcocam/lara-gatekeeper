<template>
  <fieldset class="mercadologico-selector border border-input rounded-md px-4 pb-2 dark:border-gray-700 relative">
    <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">
      {{ field.label }}
    </legend>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" v-if="field.description">{{ field.description }}</p>
    <div v-if="field.fields" class="grid grid-cols-12 gap-4">
      <div v-for="fieldOther in fields" :key="fieldOther.name" :class="getColSpanClass(fieldOther)">
        <GtFormFieldWrapper :field="fieldOther" :model-value="safeModel[fieldOther.name]"
          :error="error ? error[fieldOther.name] : null"
          @update:model-value="handleFieldUpdate(fieldOther.name, $event)" @fieldAction="handleFieldAction" />
      </div>
    </div>
  </fieldset>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { FieldConfig } from '../../../types/field';
import { router, useForm } from '@inertiajs/vue3';
import { map } from 'lodash';

// Definição das props do componente
const props = defineProps<{
  field: FieldConfig & {
    cascadingFields: Record<string, string>; // Mapeamento dos campos em cascata
  };
  error?: Record<string, string> | null | undefined;
  modelValue: any; // Valor atual do modelo
}>()

// Eventos que o componente pode emitir
const emit = defineEmits<{
  (e: 'update:model-value', value: any): void;
  (e: 'fieldAction', action: string, data: any): void;
}>();

// Parâmetros da query string atual (não utilizado atualmente)
const queryParams = ref(Object.fromEntries(new URLSearchParams(window.location.search).entries()));

// Modelo seguro para evitar mutações diretas das props
const safeModel = ref({
  ...props.modelValue,
});

// Computed para acessar os campos do formulário
const fields = computed(() => props.field.fields);

/**
 * Manipula a atualização de um campo específico
 * Remove valores dos campos dependentes quando um campo pai é alterado
 */
const handleFieldUpdate = (fieldName: string, value: any) => {
  const cascadingFields = props.field.cascadingFields;
  const newModel = ref<any>({});

  // Encontra o índice do campo que está sendo atualizado
  const cascadingFieldIndex = Object.values(cascadingFields).findIndex(cascadingField => cascadingField == fieldName);

  // Processa cada campo em cascata
  map(cascadingFields, (cascadingField: string, index: number) => {
    if (index <= cascadingFieldIndex) {
      // Mantém valores dos campos anteriores ou iguais ao campo atual
      if (fieldName == cascadingField) {
        newModel.value[cascadingField] = value;
      } else {
        newModel.value[cascadingField] = safeModel.value[cascadingField];
      }
    } else {
      // Limpa valores dos campos posteriores
      newModel.value[cascadingField] = null;
    }
  });
  console.log(newModel.value, 'newModel');
  // Realiza requisição GET para atualizar a página com novos parâmetros
  router.get(window.location.href, {
    [props.field.name]: newModel.value
  }, {
    preserveScroll: true,
    preserveState: false,
  });
};

/**
 * Repassa ações de campo para o componente pai
 */
const handleFieldAction = (action: string, data: any) => {
  emit('fieldAction', action, data);
};

/**
 * Gera classe CSS para definir o span da coluna
 */
const getColSpanClass = (field: FieldConfig) => {
  return `col-span-${field.colSpan}`;
};
</script>