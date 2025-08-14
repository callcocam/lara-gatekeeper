<script setup lang="ts">
import type { Column } from '@tanstack/vue-table'
import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem, CommandSeparator } from '@/components/ui/command'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { computed, ref } from 'vue'
import { CheckIcon, PlusCircleIcon, XIcon } from 'lucide-vue-next'

// TODO: Assumir peer dependencies ou copiar/recriar
import { cn } from '../../lib/utils'

// TODO: Usar tipos genéricos 
import type { FilterOption } from './types' // Assumindo types.ts no pacote

interface DataTableFacetedFilterProps {
  column?: Column<any> // Tornar genérico
  title?: string
  options: FilterOption[]
  multiple?: boolean // Nova prop para controlar seleção múltipla
}

const props = withDefaults(defineProps<DataTableFacetedFilterProps>(), {
  multiple: true // Por padrão permite seleção múltipla
})
console.log('[DataTableFacetedFilter] Props recebidas:', props);
const isOpen = ref(false)

const facets = computed(() => props.column?.getFacetedUniqueValues());

const selectedValues = computed(() => {
  const filterValue = props.column?.getFilterValue();

  if (!props.multiple) {
    // Para seleção única, retorna um Set com apenas um valor
    if (filterValue !== null && filterValue !== undefined) {
      return new Set([String(filterValue)]);
    }
    return new Set<string>();
  }

  // Garante que é sempre um Set de strings para seleção múltipla
  if (Array.isArray(filterValue)) {
    return new Set(filterValue.map(String));
  } else if (filterValue !== null && filterValue !== undefined) {
    // Se for uma string separada por vírgulas, divide
    const stringValue = String(filterValue);
    if (stringValue.includes(',')) {
      return new Set(stringValue.split(',').map(s => s.trim()));
    }
    return new Set([stringValue]);
  }
  return new Set<string>();
});

const handleSelect = (option: FilterOption) => {
  if (!props.column) return;

  if (!props.multiple) {
    // Seleção única - substitui o valor atual
    const currentValue = props.column.getFilterValue();
    const isCurrentlySelected = String(currentValue) === option.value;
 

    // Se já está selecionado, remove (deseleciona)
    // Se não está selecionado, seleciona
    props.column.setFilterValue(isCurrentlySelected ? undefined : option.value);

    // Fecha o popover após seleção única
    isOpen.value = false;
    return;
  }

  // Seleção múltipla - lógica original
  const currentFilter = props.column.getFilterValue();
  let currentSet = new Set<string>();

  // Normaliza o valor atual para um Set
  if (Array.isArray(currentFilter)) {
    currentSet = new Set(currentFilter.map(String));
  } else if (currentFilter !== null && currentFilter !== undefined) {
    const stringValue = String(currentFilter);
    if (stringValue.includes(',')) {
      currentSet = new Set(stringValue.split(',').map(s => s.trim()));
    } else if (stringValue.trim()) {
      currentSet = new Set([stringValue]);
    }
  }

  const isSelected = currentSet.has(option.value);

  if (isSelected) {
    currentSet.delete(option.value);
  } else {
    currentSet.add(option.value);
  }

  const filterValues = Array.from(currentSet);

  console.log('[DataTableFacetedFilter] Multiple select:', {
    option: option.value,
    isSelected,
    currentSet: Array.from(currentSet),
    filterValues
  });

  // Sempre passa um array ou undefined (nunca null ou string)
  props.column.setFilterValue(filterValues.length ? filterValues : undefined);
}

const clearFilters = () => {
  props.column?.setFilterValue(undefined);
  if (!props.multiple) {
    isOpen.value = false;
  }
}

const removeFilter = (optionValue: string) => {
  if (!props.column) return;

  if (!props.multiple) {
    props.column.setFilterValue(undefined);
    return;
  }

  const currentFilter = props.column.getFilterValue();
  let currentSet = new Set<string>();

  if (Array.isArray(currentFilter)) {
    currentSet = new Set(currentFilter.map(String));
  } else if (currentFilter !== null && currentFilter !== undefined) {
    const stringValue = String(currentFilter);
    if (stringValue.includes(',')) {
      currentSet = new Set(stringValue.split(',').map(s => s.trim()));
    } else if (stringValue.trim()) {
      currentSet = new Set([stringValue]);
    }
  }

  currentSet.delete(optionValue);
  const filterValues = Array.from(currentSet);
  props.column.setFilterValue(filterValues.length ? filterValues : undefined);
}
</script>

<template>
  <Popover v-if="column" v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button variant="outline" size="sm" class="h-8 border-dashed">
        <PlusCircleIcon class="mr-2 h-4 w-4" />
        {{ title }}
        <template v-if="selectedValues.size > 0">
          <Separator orientation="vertical" class="mx-2 h-4" />
          <Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">
            {{ selectedValues.size }}
          </Badge>
          <div class="hidden space-x-1 lg:flex">
            <Badge v-if="multiple && selectedValues.size > 2" variant="secondary" class="rounded-sm px-1 font-normal">
              {{ selectedValues.size }} selecionados
            </Badge>
            <template v-else>
              <Badge v-for="option in options.filter((opt) => selectedValues.has(opt.value))" :key="option.value"
                variant="secondary" class="rounded-sm px-1 font-normal group">
                {{ option.label }}
                <!-- Botão X para remover filtro individual (só aparece no hover para múltipla seleção) -->
                <button v-if="multiple" @click.stop="removeFilter(option.value)"
                  class="ml-1 h-3 w-3 rounded-full opacity-0 group-hover:opacity-100 hover:bg-muted transition-opacity"
                  :title="`Remover ${option.label}`">
                  <XIcon class="h-2 w-2" />
                </button>
              </Badge>
            </template>
          </div>
        </template>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[200px] p-0" align="start">
      <Command>
        <CommandInput :placeholder="title" class="h-9" />
        <CommandList>
          <CommandEmpty>Nenhum resultado.</CommandEmpty>
          <CommandGroup>
            <CommandItem v-for="option in options" :key="option.value" :value="option.value"
              @select="() => handleSelect(option)">
              <!-- Para seleção única, usa radio button style -->
              <template v-if="!multiple">
                <div :class="cn(
                  'mr-2 flex h-4 w-4 items-center justify-center rounded-full border border-primary',
                  selectedValues.has(option.value)
                    ? 'bg-primary text-primary-foreground'
                    : 'opacity-50',
                )">
                  <div v-if="selectedValues.has(option.value)" class="h-2 w-2 rounded-full bg-primary-foreground" />
                </div>
              </template>

              <!-- Para seleção múltipla, usa checkbox style -->
              <template v-else>
                <div :class="cn(
                  'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                  selectedValues.has(option.value)
                    ? 'bg-primary text-primary-foreground'
                    : 'opacity-50 [&_svg]:invisible',
                )">
                  <CheckIcon :class="cn('h-4 w-4')" />
                </div>
              </template>

              <component :is="option.icon" v-if="option.icon" class="mr-2 h-4 w-4 text-muted-foreground" />
              <span>{{ option.label }}</span>
              <span v-if="facets?.get(option.value)"
                class="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                {{ facets.get(option.value) }}
              </span>
            </CommandItem>
          </CommandGroup>

          <template v-if="selectedValues.size > 0">
            <CommandSeparator />
            <CommandGroup>
              <CommandItem :value="'_clear_'" class="justify-center text-center" @select="clearFilters">
                Limpar {{ multiple ? 'filtros' : 'filtro' }}
              </CommandItem>
            </CommandGroup>
          </template>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>