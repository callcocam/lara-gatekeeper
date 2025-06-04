<script setup lang="ts">
import type { Column } from '@tanstack/vue-table'
import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem, CommandSeparator } from '@/components/ui/command'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { computed } from 'vue'
import { CheckIcon, PlusCircleIcon } from 'lucide-vue-next'

// TODO: Assumir peer dependencies ou copiar/recriar
import { cn } from '../../lib/utils'  

// TODO: Usar tipos genéricos 
import type { FilterOption } from './types' // Assumindo types.ts no pacote

interface DataTableFacetedFilterProps {
  column?: Column<any> // Tornar genérico
  title?: string
  options: FilterOption[]
}

const props = defineProps<DataTableFacetedFilterProps>()

const facets = computed(() => props.column?.getFacetedUniqueValues());
const selectedValues = computed(() => {
    const filterValue = props.column?.getFilterValue();
    // Garante que é sempre um Set de strings
    if (Array.isArray(filterValue)) {
        return new Set(filterValue.map(String));
    }
    return new Set<string>();
});

const handleSelect = (option: FilterOption) => {
    if (!props.column) return;

    const currentFilter = props.column.getFilterValue();
    let currentSet = new Set<string>();
    if (Array.isArray(currentFilter)) {
        currentSet = new Set(currentFilter.map(String));
    }

    const isSelected = currentSet.has(option.value);

    if (isSelected) {
        currentSet.delete(option.value);
    } else {
        currentSet.add(option.value);
    }

    const filterValues = Array.from(currentSet);
    props.column.setFilterValue(filterValues.length ? filterValues : undefined);
}

const clearFilters = () => {
    props.column?.setFilterValue(undefined);
}

</script>

<template>
  <Popover v-if="column">
    <PopoverTrigger as-child>
      <Button variant="outline" size="sm" class="h-8 border-dashed">
        <PlusCircleIcon class="mr-2 h-4 w-4" />
        {{ title }}
        <template v-if="selectedValues.size > 0">
          <Separator orientation="vertical" class="mx-2 h-4" />
          <Badge
            variant="secondary"
            class="rounded-sm px-1 font-normal lg:hidden"
          >
            {{ selectedValues.size }}
          </Badge>
          <div class="hidden space-x-1 lg:flex">
            <Badge
              v-if="selectedValues.size > 2"
              variant="secondary"
              class="rounded-sm px-1 font-normal"
            >
              {{ selectedValues.size }} selecionados
            </Badge>
            <template v-else>
              <Badge
                v-for="option in options.filter((opt) => selectedValues.has(opt.value))"
                :key="option.value"
                variant="secondary"
                class="rounded-sm px-1 font-normal"
              >
                {{ option.label }}
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
            <CommandItem
              v-for="option in options"
              :key="option.value"
              :value="option.value" 
              @select="() => handleSelect(option)"
            >
              <div
                :class="cn(
                  'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                  selectedValues.has(option.value)
                    ? 'bg-primary text-primary-foreground'
                    : 'opacity-50 [&_svg]:invisible',
                )"
              >
                <CheckIcon :class="cn('h-4 w-4')" />
              </div>
              <component :is="option.icon" v-if="option.icon" class="mr-2 h-4 w-4 text-muted-foreground" />
              <span>{{ option.label }}</span>
              <span v-if="facets?.get(option.value)" class="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                {{ facets.get(option.value) }}
              </span>
            </CommandItem>
          </CommandGroup>

          <template v-if="selectedValues.size > 0">
            <CommandSeparator />
            <CommandGroup>
              <CommandItem
                 :value="'_clear_'"
                 class="justify-center text-center"
                 @select="clearFilters"
              >
                Limpar filtros
              </CommandItem>
            </CommandGroup>
          </template>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>