<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <PlusCircleIcon class="mr-2 h-4 w-4" />
                {{ filter.label }}
                <template v-if="selectedValues.length > 0">
                    <Separator orientation="vertical" class="mx-2 h-4" />
                    <Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">
                        {{ selectedValues.length }}
                    </Badge>
                    <div class="hidden space-x-1 lg:flex">
                        <Badge v-if="selectedValues.length > 2" variant="secondary"
                            class="rounded-sm px-1 font-normal">
                            {{ selectedValues.length }} selecionados
                        </Badge>
                        <template v-else>
                            <Badge v-for="option in getSelectedOptions()" :key="option.value" variant="secondary"
                                class="rounded-sm px-1 font-normal group">
                                {{ option.label }}
                                <!-- Botão X para remover filtro individual -->
                                <button @click.stop="removeFilter(option.value)"
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
                <CommandInput :placeholder="filter.label" class="h-9" />
                <CommandList>
                    <CommandEmpty>Nenhum resultado.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem v-for="option in filter.options" :key="option.value" :value="option.value"
                            @select="() => handleSelect(option)">
                            <!-- Checkbox style para seleção múltipla -->
                            <div :class="cn(
                                'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                                isSelected(option.value)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'opacity-50 [&_svg]:invisible',
                            )">
                                <CheckIcon :class="cn('h-4 w-4')" />
                            </div>

                            <component :is="option.icon" v-if="option.icon"
                                class="mr-2 h-4 w-4 text-muted-foreground" />
                            <span>{{ option.label }}</span>
                            <span v-if="option.count"
                                class="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                                {{ option.count }}
                            </span>
                        </CommandItem>
                    </CommandGroup>

                    <template v-if="selectedValues.length > 0">
                        <CommandSeparator />
                        <CommandGroup>
                            <CommandItem :value="'_clear_'" class="justify-center text-center" @select="clearFilters">
                                Limpar filtros
                            </CommandItem>
                        </CommandGroup>
                    </template>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>

<script lang="ts" setup>
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem, CommandSeparator } from '@/components/ui/command'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { computed, ref, watch } from 'vue'
import { CheckIcon, PlusCircleIcon, XIcon } from 'lucide-vue-next'
import { cn } from '../../../lib/utils'

interface Option {
    label: string;
    value: string | number;
    icon?: any;
    count?: number;
}

interface FilterProps {
    modelValue: string[] | number[] | undefined;
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option[];
    };
}

const props = defineProps<FilterProps>();

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);

// Computed para valores selecionados normalizados como array
const selectedValues = computed(() => {
    const value = props.modelValue;

    // Para seleção múltipla
    if (Array.isArray(value)) {
        return value.map(String).filter(v => v && v !== '');
    } else if (value !== null && value !== undefined) {
        // Se for uma string separada por vírgulas, divide
        const stringValue = String(value);
        if (stringValue.includes(',')) {
            return stringValue.split(',').map(s => s.trim()).filter(s => s);
        }
        return [stringValue];
    }
    return [];
});

// Função para verificar se um valor está selecionado
const isSelected = (optionValue: string | number): boolean => {
    return selectedValues.value.includes(String(optionValue));
};

// Função para obter as opções selecionadas
const getSelectedOptions = (): Option[] => {
    return props.filter.options.filter(option => isSelected(option.value));
};

// Função para lidar com seleção de opções (apenas seleção múltipla)
const handleSelect = (option: Option) => {
    const currentValues = [...selectedValues.value];
    const optionValueStr = String(option.value);
    const index = currentValues.indexOf(optionValueStr);

    if (index > -1) {
        // Remove se já está selecionado
        currentValues.splice(index, 1);
    } else {
        // Adiciona se não está selecionado
        currentValues.push(optionValueStr);
    }

    // Emite o novo valor
    const finalValue = currentValues.length > 0 ? currentValues : undefined;
    emit('update:modelValue', finalValue);
};

// Função para limpar todos os filtros
const clearFilters = () => {
    emit('update:modelValue', undefined);
};

// Função para remover um filtro específico
const removeFilter = (optionValue: string | number) => {
    const currentValues = [...selectedValues.value];
    const optionValueStr = String(optionValue);
    const index = currentValues.indexOf(optionValueStr);

    if (index > -1) {
        currentValues.splice(index, 1);
    }

    const finalValue = currentValues.length > 0 ? currentValues : undefined;
    emit('update:modelValue', finalValue);
};
</script>
