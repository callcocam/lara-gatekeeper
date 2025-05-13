<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'  
import { debounce } from 'lodash-es'

const props = defineProps<{
  id: string;
  field: {
    name: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    apiUrl: string;
    levels: number;
    labelFormat?: string;
    valueKey?: string;
    labelKey?: string;
    initialValues?: Record<number, any>;
    apiConfig?: {
      parentIdParam?: string;
      queryParams?: Record<string, any>;
      transformResponse?: (data: any) => any[];
      enableCache?: boolean;
      debounceMs?: number;
      searchParam?: string;
      enableSearch?: boolean;
      searchDebounceMs?: number;
      multiple?: boolean | Record<number, boolean>;
      maxSelections?: number | Record<number, number>;
      groupBy?: string | ((item: any) => string);
      groupLabel?: string | ((group: string) => string);
      groupOrder?: 'asc' | 'desc' | ((a: string, b: string) => number);
      pagination?: {
        enabled?: boolean;
        pageParam?: string;
        perPageParam?: string;
        perPage?: number;
        totalKey?: string;
        dataKey?: string;
      };
      itemOrder?: ((a: any, b: any) => number) | 'asc' | 'desc';
    };
    [key: string]: any;
  };
  inputProps?: Record<string, any>;
}>()
console.log(props.field)
const model = defineModel<Record<number, any | any[]>>()

// Configurações padrão
const valueKey = computed(() => props.field.valueKey || 'id')
const labelKey = computed(() => props.field.labelKey || 'name')
const labelFormat = computed(() => props.field.labelFormat || 'Nível {n}')

// Estado dos selects
const selectedValues = ref<Record<number, any | any[]>>({})
const options = ref<Record<number, any[]>>({})
const loading = ref<Record<number, boolean>>({})
const errors = ref<Record<number, string>>({})

// Cache de opções
const optionsCache = ref<Record<string, any[]>>({})

// Estado de busca
const searchTerms = ref<Record<number, string>>({})
const filteredOptions = ref<Record<number, any[]>>({})

// Estado de paginação
const pagination = ref<Record<number, {
  page: number;
  perPage: number;
  total: number;
  hasMore: boolean;
  loading: boolean;
}>>({})

// Função para gerar chave do cache
function getCacheKey(level: number, parentId: string | null): string {
  const queryParams = props.field.apiConfig?.queryParams || {}
  return `${level}-${parentId}-${JSON.stringify(queryParams)}`
}

// Inicializar paginação para cada nível
for (let i = 1; i <= props.field.levels; i++) {
  pagination.value[i] = {
    page: 1,
    perPage: props.field.apiConfig?.pagination?.perPage || 20,
    total: 0,
    hasMore: false,
    loading: false
  }
}

// Carregar opções para um nível específico
const loadOptions = debounce(async (level: number, page = 1) => {
  const parentIdParam = props.field.apiConfig?.parentIdParam || 'parent_id'
  const queryParams = props.field.apiConfig?.queryParams || {}
  const transformResponse = props.field.apiConfig?.transformResponse || ((data: any) => data)
  const enableCache = props.field.apiConfig?.enableCache ?? true
  const debounceMs = props.field.apiConfig?.debounceMs ?? 300
  const paginationConfig = props.field.apiConfig?.pagination || {}
  const isPaginationEnabled = paginationConfig.enabled ?? false

  if (level === 1) {
    // Primeiro nível: busca onde parent_id é null
    loading.value[level] = true
    errors.value[level] = ''
    
    const cacheKey = getCacheKey(level, 'null')
    
    try {
      let data
      if (enableCache && optionsCache.value[cacheKey] && !isPaginationEnabled) {
        data = optionsCache.value[cacheKey]
      } else {
        const response = await axios.get(props.field.apiUrl, {
          params: {
            [parentIdParam]: 'null',
            ...(isPaginationEnabled ? {
              [paginationConfig.pageParam || 'page']: page,
              [paginationConfig.perPageParam || 'per_page']: pagination.value[level].perPage
            } : {}),
            ...queryParams
          }
        })

        if (isPaginationEnabled) {
          const responseData = response.data[paginationConfig.dataKey || 'data'] || response.data
          const total = response.data[paginationConfig.totalKey || 'total'] || responseData.length
          
          pagination.value[level] = {
            ...pagination.value[level],
            page,
            total,
            hasMore: (page * pagination.value[level].perPage) < total,
            loading: false
          }
          
          data = transformResponse(responseData)
          if (page === 1) {
            options.value[level] = data
          } else {
            options.value[level] = [...options.value[level], ...data]
          }
        } else {
          data = transformResponse(response.data)
          options.value[level] = data
          if (enableCache) {
            optionsCache.value[cacheKey] = data
          }
        }
      }
      
      if (!isPaginationEnabled) {
        options.value[level] = data
      }
      
      if (!options.value[level].length) {
        errors.value[level] = 'Nenhuma opção disponível'
      }
    } catch (error) {
      console.error('Erro ao carregar opções:', error)
      options.value[level] = []
      errors.value[level] = 'Erro ao carregar opções'
    } finally {
      loading.value[level] = false
    }
  } else {
    // Níveis subsequentes: busca baseado no valor selecionado no nível anterior
    const parentValue = selectedValues.value[level - 1]
    if (!parentValue) {
      options.value[level] = []
      errors.value[level] = 'Selecione um valor no nível anterior'
      return
    }

    loading.value[level] = true
    errors.value[level] = ''
    
    const cacheKey = getCacheKey(level, parentValue[valueKey.value])
    
    try {
      let data
      if (enableCache && optionsCache.value[cacheKey] && !isPaginationEnabled) {
        data = optionsCache.value[cacheKey]
      } else {
        const response = await axios.get(props.field.apiUrl, {
          params: {
            [parentIdParam]: parentValue[valueKey.value],
            ...(isPaginationEnabled ? {
              [paginationConfig.pageParam || 'page']: page,
              [paginationConfig.perPageParam || 'per_page']: pagination.value[level].perPage
            } : {}),
            ...queryParams
          }
        })

        if (isPaginationEnabled) {
          const responseData = response.data[paginationConfig.dataKey || 'data'] || response.data
          const total = response.data[paginationConfig.totalKey || 'total'] || responseData.length
          
          pagination.value[level] = {
            ...pagination.value[level],
            page,
            total,
            hasMore: (page * pagination.value[level].perPage) < total,
            loading: false
          }
          
          data = transformResponse(responseData)
          if (page === 1) {
            options.value[level] = data
          } else {
            options.value[level] = [...options.value[level], ...data]
          }
        } else {
          data = transformResponse(response.data)
          options.value[level] = data
          if (enableCache) {
            optionsCache.value[cacheKey] = data
          }
        }
      }
      
      if (!isPaginationEnabled) {
        options.value[level] = data
      }
      
      if (!options.value[level].length) {
        errors.value[level] = 'Nenhuma opção disponível para este nível'
      }
    } catch (error) {
      console.error('Erro ao carregar opções:', error)
      options.value[level] = []
      errors.value[level] = 'Erro ao carregar opções'
    } finally {
      loading.value[level] = false
    }
  }
}, props.field.apiConfig?.debounceMs ?? 300)

// Função para filtrar opções
const filterOptions = debounce((level: number, term: string) => {
  if (!term) {
    filteredOptions.value[level] = options.value[level]
    return
  }

  const searchParam = props.field.apiConfig?.searchParam || 'search'
  const enableSearch = props.field.apiConfig?.enableSearch ?? true

  if (enableSearch) {
    // Busca via API
    const parentIdParam = props.field.apiConfig?.parentIdParam || 'parent_id'
    const queryParams = props.field.apiConfig?.queryParams || {}
    const parentValue = level === 1 ? null : selectedValues.value[level - 1]?.[valueKey.value]

    axios.get(props.field.apiUrl, {
      params: {
        [parentIdParam]: parentValue,
        [searchParam]: term,
        ...queryParams
      }
    }).then(response => {
      const transformResponse = props.field.apiConfig?.transformResponse || ((data: any) => data)
      filteredOptions.value[level] = transformResponse(response.data)
    }).catch(error => {
      console.error('Erro ao buscar opções:', error)
      filteredOptions.value[level] = []
    })
  } else {
    // Filtro local
    const termLower = term.toLowerCase()
    filteredOptions.value[level] = options.value[level].filter(option => 
      String(option[labelKey.value]).toLowerCase().includes(termLower)
    )
  }
}, props.field.apiConfig?.searchDebounceMs ?? 300)

// Inicializar com valores iniciais se fornecidos
if (props.field.initialValues) {
  selectedValues.value = { ...props.field.initialValues }
  // Carregar opções para cada nível inicial
  Object.keys(props.field.initialValues).forEach(async (level) => {
    await loadOptions(parseInt(level))
  })
} else {
  // Carregar apenas o primeiro nível se não houver valores iniciais
  loadOptions(1)
}

// Função para resetar o componente
function reset() {
  selectedValues.value = {}
  options.value = {}
  errors.value = {}
  loadOptions(1)
}

// Função para limpar cache
function clearCache() {
  optionsCache.value = {}
}

// Expor funções
defineExpose({ 
  reset,
  clearCache
})

// Validação
const isValid = computed(() => {
  if (!props.field.required) return true
  
  // Verifica se todos os níveis até o último selecionado estão preenchidos
  const lastSelectedLevel = Math.max(...Object.keys(selectedValues.value).map(Number))
  for (let i = 1; i <= lastSelectedLevel; i++) {
    if (!selectedValues.value[i]) return false
  }
  return true
})

// Observar mudanças nos valores selecionados
watch(selectedValues, (newValues) => {
  // Limpar valores dos níveis subsequentes quando um nível é alterado
  Object.keys(newValues).forEach((level) => {
    const currentLevel = parseInt(level)
    for (let i = currentLevel + 1; i <= props.field.levels; i++) {
      selectedValues.value[i] = null
      options.value[i] = []
      errors.value[i] = ''
    }
  })

  // Carregar opções para o próximo nível
  const lastSelectedLevel = Math.max(...Object.keys(newValues).map(Number))
  if (lastSelectedLevel < props.field.levels) {
    loadOptions(lastSelectedLevel + 1)
  }

  // Emitir valor atualizado
  model.value = newValues
}, { deep: true })

// Observar mudanças nos termos de busca
watch(searchTerms, (newTerms) => {
  Object.entries(newTerms).forEach(([level, term]) => {
    filterOptions(parseInt(level), term)
  })
}, { deep: true })

// Observar mudanças nas opções
watch(options, (newOptions) => {
  Object.keys(newOptions).forEach(level => {
    const levelNum = parseInt(level)
    const term = searchTerms.value[levelNum] || ''
    filterOptions(levelNum, term)
  })
}, { deep: true })

// Funções auxiliares para seleção múltipla
function isMultiple(level: number): boolean {
  if (typeof props.field.apiConfig?.multiple === 'boolean') {
    return props.field.apiConfig.multiple
  }
  return props.field.apiConfig?.multiple?.[level] ?? false
}

function getMaxSelections(level: number): number | undefined {
  if (typeof props.field.apiConfig?.maxSelections === 'number') {
    return props.field.apiConfig.maxSelections
  }
  return props.field.apiConfig?.maxSelections?.[level]
}

function toggleSelection(level: number, option: any) {
  const currentValue = selectedValues.value[level] || []
  const isArray = Array.isArray(currentValue)
  const value = isArray ? currentValue : [currentValue]
  
  const index = value.findIndex(v => v[valueKey.value] === option[valueKey.value])
  const maxSelections = getMaxSelections(level)
  
  if (index === -1) {
    // Adicionar seleção
    if (!maxSelections || value.length < maxSelections) {
      selectedValues.value[level] = isMultiple(level) ? [...value, option] : option
    }
  } else {
    // Remover seleção
    if (isMultiple(level)) {
      value.splice(index, 1)
      selectedValues.value[level] = value
    } else {
      selectedValues.value[level] = null
    }
  }
}

function isSelected(level: number, option: any): boolean {
  const currentValue = selectedValues.value[level]
  if (!currentValue) return false
  
  if (Array.isArray(currentValue)) {
    return currentValue.some(v => v[valueKey.value] === option[valueKey.value])
  }
  
  return currentValue[valueKey.value] === option[valueKey.value]
}

// Funções para grupos
function getGroupKey(item: any): string {
  if (!props.field.apiConfig?.groupBy) return ''
  
  if (typeof props.field.apiConfig.groupBy === 'function') {
    return props.field.apiConfig.groupBy(item)
  }
  
  return item[props.field.apiConfig.groupBy] || 'Sem grupo'
}

function getGroupLabel(group: string): string {
  if (!props.field.apiConfig?.groupLabel) return group
  
  if (typeof props.field.apiConfig.groupLabel === 'function') {
    return props.field.apiConfig.groupLabel(group)
  }
  
  return props.field.apiConfig.groupLabel
}

function sortGroups(groups: string[]): string[] {
  if (!props.field.apiConfig?.groupOrder) return groups
  
  if (typeof props.field.apiConfig.groupOrder === 'function') {
    return [...groups].sort(props.field.apiConfig.groupOrder)
  }
  
  return [...groups].sort((a, b) => {
    if (props.field.apiConfig?.groupOrder === 'desc') {
      return b.localeCompare(a)
    }
    return a.localeCompare(b)
  })
}

// Função para ordenar opções
function sortOptions(options: any[]): any[] {
  const itemOrder = props.field.apiConfig?.itemOrder
  const labelKeyVal = labelKey.value
  if (!itemOrder) return options
  if (typeof itemOrder === 'function') {
    return [...options].sort(itemOrder)
  }
  if (itemOrder === 'asc') {
    return [...options].sort((a, b) => String(a[labelKeyVal]).localeCompare(String(b[labelKeyVal]), undefined, { sensitivity: 'base' }))
  }
  if (itemOrder === 'desc') {
    return [...options].sort((a, b) => String(b[labelKeyVal]).localeCompare(String(a[labelKeyVal]), undefined, { sensitivity: 'base' }))
  }
  return options
}

// Computed para opções agrupadas
const groupedOptions = computed(() => {
  const result: Record<number, Record<string, any[]>> = {}
  
  Object.entries(filteredOptions.value).forEach(([level, options]) => {
    const levelNum = parseInt(level)
    if (!options?.length) {
      result[levelNum] = {}
      return
    }
    
    const groups: Record<string, any[]> = {}
    options.forEach(option => {
      const group = getGroupKey(option)
      if (!groups[group]) {
        groups[group] = []
      }
      groups[group].push(option)
    })
    // Ordenar opções dentro de cada grupo
    Object.keys(groups).forEach(group => {
      groups[group] = sortOptions(groups[group])
    })
    result[levelNum] = groups
  })
  
  return result
})

// Computed para opções não agrupadas (usado no template)
const sortedFilteredOptions = computed(() => {
  const result: Record<number, any[]> = {}
  Object.entries(filteredOptions.value).forEach(([level, options]) => {
    result[parseInt(level)] = sortOptions(options)
  })
  return result
})

// Função para carregar mais opções
async function loadMore(level: number) {
  if (pagination.value[level].loading || !pagination.value[level].hasMore) return
  
  pagination.value[level].loading = true
  await loadOptions(level, pagination.value[level].page + 1)
}

// Funções para seleção em grupo
function toggleGroupSelection(level: number, group: string) {
  if (!isMultiple(level)) return
  
  const groupOptions = groupedOptions.value[level][group] || []
  const currentValue = selectedValues.value[level] || []
  const isArray = Array.isArray(currentValue)
  const value = isArray ? currentValue : [currentValue]
  
  // Verifica se todos os itens do grupo já estão selecionados
  const allSelected = groupOptions.every(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  )
  
  const maxSelections = getMaxSelections(level)
  
  if (allSelected) {
    // Remove todos os itens do grupo
    selectedValues.value[level] = value.filter(v => 
      !groupOptions.some(option => option[valueKey.value] === v[valueKey.value])
    )
  } else {
    // Adiciona todos os itens do grupo que ainda não estão selecionados
    const newValue = [...value]
    groupOptions.forEach(option => {
      if (!value.some(v => v[valueKey.value] === option[valueKey.value])) {
        if (!maxSelections || newValue.length < maxSelections) {
          newValue.push(option)
        }
      }
    })
    selectedValues.value[level] = newValue
  }
}

function isGroupFullySelected(level: number, group: string): boolean {
  if (!isMultiple(level)) return false
  
  const groupOptions = groupedOptions.value[level][group] || []
  const currentValue = selectedValues.value[level] || []
  const value = Array.isArray(currentValue) ? currentValue : [currentValue]
  
  return groupOptions.every(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  )
}

function isGroupPartiallySelected(level: number, group: string): boolean {
  if (!isMultiple(level)) return false
  
  const groupOptions = groupedOptions.value[level][group] || []
  const currentValue = selectedValues.value[level] || []
  const value = Array.isArray(currentValue) ? currentValue : [currentValue]
  
  const selectedCount = groupOptions.filter(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  ).length
  
  return selectedCount > 0 && selectedCount < groupOptions.length
}

// Funções para seleção em grupo
function toggleAllSelections(level: number) {
  if (!isMultiple(level)) return
  
  const currentValue = selectedValues.value[level] || []
  const isArray = Array.isArray(currentValue)
  const value = isArray ? currentValue : [currentValue]
  
  // Verifica se todos os itens já estão selecionados
  const allOptions = Object.values(groupedOptions.value[level] || {}).flat()
  const allSelected = allOptions.every(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  )
  
  const maxSelections = getMaxSelections(level)
  
  if (allSelected) {
    // Remove todas as seleções
    selectedValues.value[level] = []
  } else {
    // Adiciona todos os itens que ainda não estão selecionados
    const newValue = [...value]
    allOptions.forEach(option => {
      if (!value.some(v => v[valueKey.value] === option[valueKey.value])) {
        if (!maxSelections || newValue.length < maxSelections) {
          newValue.push(option)
        }
      }
    })
    selectedValues.value[level] = newValue
  }
}

function isAllSelected(level: number): boolean {
  if (!isMultiple(level)) return false
  
  const currentValue = selectedValues.value[level] || []
  const value = Array.isArray(currentValue) ? currentValue : [currentValue]
  const allOptions = Object.values(groupedOptions.value[level] || {}).flat()
  
  return allOptions.length > 0 && allOptions.every(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  )
}

function isPartiallySelected(level: number): boolean {
  if (!isMultiple(level)) return false
  
  const currentValue = selectedValues.value[level] || []
  const value = Array.isArray(currentValue) ? currentValue : [currentValue]
  const allOptions = Object.values(groupedOptions.value[level] || {}).flat()
  
  const selectedCount = allOptions.filter(option => 
    value.some(v => v[valueKey.value] === option[valueKey.value])
  ).length
  
  return selectedCount > 0 && selectedCount < allOptions.length
}

function getTotalOptions(level: number): number {
  return Object.values(groupedOptions.value[level] || {}).reduce(
    (total, group) => total + group.length,
    0
  )
}
</script>

<template>
  <div :id="props.id" class="space-y-4">
    <Label v-if="props.field.label" :for="props.id">
      {{ props.field.label }}
      <span v-if="props.field.required" class="text-red-500">*</span>
    </Label>

    <div v-if="props.field.description" class="text-sm text-gray-500">
      {{ props.field.description }}
    </div>

    <div class="space-y-2">
      <div v-for="level in props.field.levels" :key="level" class="flex items-center gap-2">
        <Label class="w-24">{{ labelFormat.replace('{n}', level.toString()) }}</Label>
        <div class="flex-1 space-y-1">
          <div class="relative">
            <input
              v-model="searchTerms[level]"
              type="text"
              :placeholder="'Buscar...'"
              class="w-full px-3 py-2 border rounded-md"
              :disabled="props.field.disabled || loading[level]"
              :class="{
                'border-red-500': errors[level] || (props.field.required && !selectedValues[level])
              }"
            />
            
            <!-- Seleções atuais -->
            <div v-if="selectedValues[level]" class="mt-2 flex flex-wrap gap-2">
              <div
                v-for="selected in (Array.isArray(selectedValues[level]) ? selectedValues[level] : [selectedValues[level]])"
                :key="selected[valueKey]"
                class="flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-sm"
              >
                <span>{{ selected[labelKey] }}</span>
                <button
                  type="button"
                  class="text-blue-600 hover:text-blue-800"
                  @click="toggleSelection(level, selected)"
                >
                  ×
                </button>
              </div>
            </div>

            <!-- Lista de opções agrupadas -->
            <div
              v-if="searchTerms[level] || sortedFilteredOptions[level]?.length"
              class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-auto"
            >
              <!-- Cabeçalho com seleção de todos -->
              <div 
                v-if="props.field.apiConfig?.groupBy && isMultiple(level)"
                class="px-3 py-2 bg-gray-100 border-b flex items-center justify-between sticky top-0 z-10"
              >
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :checked="isAllSelected(level)"
                    :indeterminate="isPartiallySelected(level)"
                    class="rounded"
                    @click="toggleAllSelections(level)"
                  />
                  <span class="text-sm font-medium">Selecionar todos</span>
                </div>
                <span class="text-xs text-gray-500">
                  {{ getTotalOptions(level) }} itens
                </span>
              </div>

              <template v-if="props.field.apiConfig?.groupBy">
                <div
                  v-for="group in sortGroups(Object.keys(groupedOptions[level] || {}))"
                  :key="group"
                  class="border-b last:border-b-0"
                >
                  <div 
                    class="px-3 py-1 bg-gray-50 text-sm font-medium text-gray-700 flex items-center justify-between"
                  >
                    <span>{{ getGroupLabel(group) }}</span>
                    <div v-if="isMultiple(level)" class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        :checked="isGroupFullySelected(level, group)"
                        :indeterminate="isGroupPartiallySelected(level, group)"
                        class="rounded"
                        @click.stop="toggleGroupSelection(level, group)"
                      />
                      <span class="text-xs text-gray-500">
                        {{ groupedOptions[level][group].length }} itens
                      </span>
                    </div>
                  </div>
                  <div
                    v-for="option in groupedOptions[level][group]"
                    :key="option[valueKey]"
                    class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center gap-2"
                    :class="{ 'bg-blue-50': isSelected(level, option) }"
                    @click="toggleSelection(level, option)"
                  >
                    <input
                      v-if="isMultiple(level)"
                      type="checkbox"
                      :checked="isSelected(level, option)"
                      class="rounded"
                      @click.stop
                    />
                    <span>{{ option[labelKey] }}</span>
                  </div>
                </div>
              </template>
              <template v-else>
                <div
                  v-for="option in sortedFilteredOptions[level]"
                  :key="option[valueKey]"
                  class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center gap-2"
                  :class="{ 'bg-blue-50': isSelected(level, option) }"
                  @click="toggleSelection(level, option)"
                >
                  <input
                    v-if="isMultiple(level)"
                    type="checkbox"
                    :checked="isSelected(level, option)"
                    class="rounded"
                    @click.stop
                  />
                  <span>{{ option[labelKey] }}</span>
                </div>
              </template>
            </div>
          </div>

          <div v-if="errors[level]" class="text-sm text-red-500">
            {{ errors[level] }}
          </div>
          <div v-if="loading[level]" class="text-sm text-gray-500">
            Carregando...
          </div>
          <div v-if="getMaxSelections(level)" class="text-sm text-gray-500">
            Máximo de {{ getMaxSelections(level) }} seleções
          </div>
        </div>
      </div>
    </div>
  </div>
</template> 