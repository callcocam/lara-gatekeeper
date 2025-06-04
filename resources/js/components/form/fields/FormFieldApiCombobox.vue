<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'  
import { Combobox, ComboboxItem } from '@/components/ui/combobox'
import { Label } from '@/components/ui/label'

const props = defineProps<{
  id: string;
  field: {
    name: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    apiUrl?: string;
    options?: Record<string, string> | Array<{ value: string | number, label: string }>;
    [key: string]: any;
  };
  inputProps?: Record<string, any>;
}>()

const model = defineModel<string | number | null>()
const loading = ref(false)
const options = ref<{ value: string | number, label: string }[]>([])

const labelText = computed(() => props.field.label || props.field.name)

function normalizeOptions(raw: any): { value: string | number, label: string }[] {
  if (Array.isArray(raw)) {
    return raw.map(opt => typeof opt === 'object' ? { value: opt.value, label: String(opt.label) } : { value: opt, label: String(opt) })
  } else if (raw && typeof raw === 'object') {
    return Object.entries(raw).map(([value, label]) => ({ value, label: String(label) }))
  }
  return []
}

async function fetchOptions() {
  if (!props.field.apiUrl) return
  loading.value = true
  try {
    const response = await axios.get(props.field.apiUrl)
    // Espera array de objetos { value, label } ou objeto { value: label }
    options.value = normalizeOptions(response.data)
  } catch (e) {
    options.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.field.options) {
    options.value = normalizeOptions(props.field.options)
  } else if (props.field.apiUrl) {
    fetchOptions()
  }
})

function onFocus() {
  if (!options.value.length && props.field.apiUrl) {
    fetchOptions()
  }
}
</script>

<template>
  <div :id="props.id" class="py-1">
    <Label v-if="labelText" :for="props.id + '-combobox'" class="font-normal cursor-pointer select-none">
      {{ labelText }}
      <span v-if="props.field.required" class="text-red-500 ml-1">*</span>
    </Label>
    <Combobox
      :id="props.id + '-combobox'"
      v-model="model"
      :disabled="props.field.disabled"
      v-bind="props.inputProps"
      @focus="onFocus"
      class="w-full"
    >
      <template v-if="loading">
        <ComboboxItem disabled value="" label="Carregando..." />
      </template>
      <template v-else>
        <ComboboxItem
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
          :label="opt.label"
        >
          {{ opt.label }}
        </ComboboxItem>
        <ComboboxItem v-if="!options.length" disabled value="" label="Nenhuma opção encontrada" />
      </template>
    </Combobox>
    <span v-if="props.field.description" class="text-xs text-gray-500 ml-2">{{ props.field.description }}</span>
  </div>
</template> 