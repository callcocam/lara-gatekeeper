<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="h-8 border-dashed">
                <PlusCircleIcon class="mr-2 h-4 w-4" />
                {{ filter.label }}
                <template v-if="selectedOptions.length > 0">
                    <Separator orientation="vertical" class="mx-2 h-4" />
                    <Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">
                        {{ selectedOptions.length }}
                    </Badge>
                    <div class="hidden space-x-1 lg:flex">
                        <Badge v-if="selectedOptions.length > 2" variant="secondary"
                            class="rounded-sm px-1 font-normal">
                            {{ selectedOptions.length }} selecionados
                        </Badge>
                        <template v-else>
                            <Badge v-for="option in selectedOptions" :key="option.value" variant="secondary"
                                class="rounded-sm px-1 font-normal group">
                                {{ option.label }}
                                <button @click.stop="removeOption(option.value)"
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
                <CommandInput :placeholder="`Buscar ${filter.label.toLowerCase()}...`" class="h-9" />
                <CommandList>
                    <CommandEmpty>Nenhum resultado encontrado.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem v-for="option in filter.options" :key="option.value" :value="option.value"
                            @select="() => handleSelect(option)" class="cursor-pointer flex items-center">
                            <div :class="cn(
                                'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                                isSelected(option.value)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'opacity-50 [&_svg]:invisible',
                            )">
                                <CheckIcon :class="cn('h-4 w-4')" />
                            </div>
                            <span>{{ option.label }}</span>
                            <span v-if="option.count"
                                class="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                                {{ option.count }}
                            </span>
                        </CommandItem>
                    </CommandGroup>
                    <template v-if="selectedOptions.length > 0">
                        <CommandSeparator />
                        <CommandGroup>
                            <CommandItem :value="'_clear_'" class="justify-center text-center" @select="clearFilters">
                                Limpar seleção
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
import { computed, ref } from 'vue'
import { CheckIcon, PlusCircleIcon, XIcon } from 'lucide-vue-next'
import { cn } from '../../../lib/utils'

interface Option {
    label: string;
    value: string | number;
    count?: number;
}

interface FilterProps {
    modelValue: any;
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option[];
        multiple?: boolean;
    };
}

const props = defineProps<FilterProps>();

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);

// Computed para normalizar valores selecionados como array
const selectedValues = computed(() => {
    const value = props.modelValue;

    if (props.filter.multiple) {
        if (Array.isArray(value)) {
            return value.map(String);
        } else if (value !== null && value !== undefined && value !== '') {
            return [String(value)];
        }
        return [];
    } else {
        return value !== null && value !== undefined && value !== '' ? [String(value)] : [];
    }
});

// Computed para opções selecionadas
const selectedOptions = computed(() => {
    return props.filter.options.filter(option =>
        selectedValues.value.includes(String(option.value))
    );
});

// Função para verificar se um valor está selecionado
const isSelected = (value: string | number): boolean => {
    return selectedValues.value.includes(String(value));
};

// Função para lidar com seleção de opções
const handleSelect = (option: Option) => {
    if (props.filter.multiple) {
        // Seleção múltipla
        const currentValues = [...selectedValues.value];
        const optionValueStr = String(option.value);
        const index = currentValues.indexOf(optionValueStr);

        if (index > -1) {
            currentValues.splice(index, 1);
        } else {
            currentValues.push(optionValueStr);
        }

        const finalValue = currentValues.length > 0 ? currentValues : undefined;
        emit('update:modelValue', finalValue);
    } else {
        // Seleção única
        if (isSelected(option.value)) {
            emit('update:modelValue', undefined);
        } else {
            emit('update:modelValue', option.value);
        }
        isOpen.value = false;
    }
};

// Função para remover uma opção específica
const removeOption = (optionValue: string | number) => {
    if (props.filter.multiple) {
        const currentValues = selectedValues.value.filter(v => v !== String(optionValue));
        const finalValue = currentValues.length > 0 ? currentValues : undefined;
        emit('update:modelValue', finalValue);
    } else {
        emit('update:modelValue', undefined);
    }
};

// Função para limpar todas as seleções
const clearFilters = () => {
    emit('update:modelValue', undefined);
    if (!props.filter.multiple) {
        isOpen.value = false;
    }
};
</script>