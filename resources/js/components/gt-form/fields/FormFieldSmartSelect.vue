<script setup lang="ts">
import { computed, ref, watch, onMounted, inject } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Loader2, Search } from 'lucide-vue-next'

interface SmartOption {
  value: string | number;
  label: string;
  description?: string;
  color?: string;
  icon?: string;
  disabled?: boolean;
  actions?: {
    show_fields?: string[];
    hide_fields?: string[];
    api_call?: string;
    callback?: string;
  };
  [key: string]: any;
}

const props = defineProps<{
  id: string;
  field: {
    name: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    placeholder?: string;
    // Configurações do SmartSelect
    apiUrl?: string;
    searchable?: boolean;
    displayTemplate?: string;
    withActions?: {
      onSelect?: string;
      showFields?: string[];
      hideFields?: string[];
      loadRelated?: string;
    };
    options?: Record<string, SmartOption | string>;
    [key: string]: any;
  };
  inputProps?: Record<string, any>;
}>()

const emit = defineEmits<{
  fieldAction: [action: string, data: any];
}>()

const model = defineModel<string | number | null>()

// Estados internos
const loading = ref(false)
const searchTerm = ref('')
const apiOptions = ref<SmartOption[]>([])
const isOpen = ref(false)

// Computed para opções processadas
const processedOptions = computed(() => {
  let options: SmartOption[] = []

  // Se tem apiUrl, usa opções da API
  if (props.field.apiUrl && apiOptions.value.length > 0) {
    options = apiOptions.value
  } 
  // Senão, processa opções estáticas
  else if (props.field.options) {
    options = Object.entries(props.field.options).map(([optionValue, option]) => {
      if (typeof option === 'string') {
        return { value: optionValue, label: option }
      }
      return { ...option, value: optionValue }
    })
  }

  // Aplica filtro de busca se searchable
  if (props.field.searchable && searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    options = options.filter(option => 
      option.label.toLowerCase().includes(search) ||
      option.description?.toLowerCase().includes(search)
    )
  }

  return options
})

// Opção selecionada atual
const selectedOption = computed(() => {
  if (!model.value) return null
  return processedOptions.value.find(opt => opt.value.toString() === model.value?.toString())
})

// Placeholder dinâmico
const placeholder = computed(() => {
  if (loading.value) return 'Carregando...'
  return props.field.placeholder || 'Selecione uma opção'
})

// Template de exibição personalizado
const formatOptionDisplay = (option: SmartOption) => {
  if (props.field.displayTemplate) {
    let template = props.field.displayTemplate
    Object.keys(option).forEach(key => {
      template = template.replace(`{{ ${key} }}`, option[key] || '')
    })
    return template
  }
  return option.label
}

// Carrega opções via API
const loadApiOptions = async () => {
  if (!props.field.apiUrl) return

  loading.value = true
  try {
    const response = await fetch(props.field.apiUrl, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Erro ao carregar opções')
    
    const data = await response.json()
    apiOptions.value = data.data || data || []
    
    console.log(`[SmartSelect:${props.field.name}] Loaded ${apiOptions.value.length} options from API`)
  } catch (error) {
    console.error(`[SmartSelect:${props.field.name}] API Error:`, error)
    apiOptions.value = []
  } finally {
    loading.value = false
  }
}

// Executa ações quando valor muda
const handleValueChange = async (newValue: string | number) => {
  console.log(`[SmartSelect:${props.field.name}] Value changed to:`, newValue)
  
  const option = processedOptions.value.find(opt => opt.value.toString() === newValue.toString())
  if (!option) return

  // Ações do campo global
  if (props.field.withActions) {
    const actions = props.field.withActions

    // Mostrar/ocultar campos
    if (actions.showFields?.length) {
      emit('fieldAction', 'showFields', actions.showFields)
    }
    if (actions.hideFields?.length) {
      emit('fieldAction', 'hideFields', actions.hideFields)
    }

    // Callback JavaScript
    if (actions.onSelect) {
      emit('fieldAction', 'callback', { 
        callback: actions.onSelect, 
        value: newValue, 
        option 
      })
    }

    // Carregar dados relacionados
    if (actions.loadRelated) {
      emit('fieldAction', 'loadRelated', {
        url: actions.loadRelated,
        value: newValue,
        option
      })
    }
  }

  // Ações específicas da opção
  if (option.actions) {
    const actions = option.actions

    // Mostrar/ocultar campos
    if (actions.show_fields?.length) {
      emit('fieldAction', 'showFields', actions.show_fields)
    }
    if (actions.hide_fields?.length) {
      emit('fieldAction', 'hideFields', actions.hide_fields)
    }

    // API call específica
    if (actions.api_call) {
      emit('fieldAction', 'apiCall', {
        url: actions.api_call,
        value: newValue,
        option
      })
    }

    // Callback específico
    if (actions.callback) {
      emit('fieldAction', 'callback', {
        callback: actions.callback,
        value: newValue,
        option
      })
    }
  }
}

// Watchers
watch(model, (newValue) => {
  if (newValue) {
    handleValueChange(newValue)
  }
})

watch(searchTerm, (newTerm) => {
  console.log(`[SmartSelect:${props.field.name}] Search term:`, newTerm)
})

// Lifecycle
onMounted(() => {
  if (props.field.apiUrl) {
    loadApiOptions()
  }
})

// Computed para valor do modelo
const modelValueForSelect = computed({
  get: () => model.value?.toString() ?? '',
  set: (value) => {
    const processedValue = value === '' ? null : value
    console.log(`[SmartSelect:${props.field.name}] Setting value:`, processedValue)
    model.value = processedValue
  }
})
</script>

<template>
  <div class="space-y-2 mt-2">
    <!-- Campo de busca (se searchable) -->
    <div v-if="field.searchable" class="relative">
      <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <Input
        v-model="searchTerm"
        placeholder="Buscar opções..."
        class="pl-9"
        :disabled="loading"
      />
    </div>

    <!-- Select principal -->
    <Select v-model="modelValueForSelect" @open-change="isOpen = $event">
      <SelectTrigger 
        :id="props.id" 
        class="w-full" 
        v-bind="props.inputProps"
        :disabled="props.field.disabled || loading"
      >
        <div class="flex items-center gap-2 flex-1">
          <!-- Ícone de loading -->
          <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
          
          <!-- Ícone da opção selecionada -->
          <component 
            v-else-if="selectedOption?.icon" 
            :is="selectedOption.icon" 
            class="h-4 w-4" 
          />
          
          <!-- Valor selecionado -->
          <SelectValue :placeholder="placeholder">
            <div v-if="selectedOption" class="flex items-center gap-2">
              <!-- Badge de cor (se definida) -->
              <Badge 
                v-if="selectedOption.color" 
                :variant="selectedOption.color as any"
                class="w-3 h-3 rounded-full p-0"
              />
              
              <!-- Texto formatado -->
              <span>{{ formatOptionDisplay(selectedOption) }}</span>
            </div>
          </SelectValue>
        </div>
      </SelectTrigger>
      
      <SelectContent>
        <!-- Mensagem de loading -->
        <div v-if="loading" class="flex items-center justify-center py-4">
          <Loader2 class="h-4 w-4 animate-spin mr-2" />
          <span class="text-sm text-muted-foreground">Carregando opções...</span>
        </div>

        <!-- Mensagem de nenhum resultado -->
        <div v-else-if="processedOptions.length === 0" class="py-4 text-center">
          <span class="text-sm text-muted-foreground">
            {{ field.searchable && searchTerm ? 'Nenhum resultado encontrado' : 'Nenhuma opção disponível' }}
          </span>
        </div>

        <!-- Opções -->
        <SelectItem
          v-for="option in processedOptions"
          :key="option.value"
          :value="option.value.toString()"
          :disabled="option.disabled"
          class="flex items-center gap-2"
        >
          <div class="flex items-center gap-2 flex-1">
            <!-- Ícone da opção -->
            <component 
              v-if="option.icon" 
              :is="option.icon" 
              class="h-4 w-4" 
            />
            
            <!-- Badge de cor -->
            <Badge 
              v-if="option.color" 
              :variant="option.color as any"
              class="w-3 h-3 rounded-full p-0"
            />
            
            <!-- Conteúdo da opção -->
            <div class="flex-1">
              <div class="font-medium">{{ option.label }}</div>
              <div v-if="option.description" class="text-xs text-muted-foreground">
                {{ option.description }}
              </div>
            </div>
          </div>
        </SelectItem>
      </SelectContent>
    </Select>

    <!-- Informações da opção selecionada -->
    <div v-if="selectedOption?.description && !field.searchable" class="text-xs text-muted-foreground">
      {{ selectedOption.description }}
    </div>
  </div>
</template> 