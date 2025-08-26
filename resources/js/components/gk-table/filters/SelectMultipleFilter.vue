<template>
    <Popover>
        <PopoverTrigger>
            <Button variant="outline" size="sm" class="h-9 border-dashed">
                <PlusCircleIcon class="mr-2 h-4 w-4" />
                <span>
                    {{ filter.label }}
                </span>
                <span v-if="countSelected > 0" class="ml-2 text-xs text-muted-foreground">
                    ({{ countSelected }})
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="p-0">
            <Command>
                <CommandInput :placeholder="filter.label" class="h-9" />
                <CommandList>
                    <CommandEmpty>No results found.</CommandEmpty>
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
                            {{ option.label }}
                        </CommandItem>
                    </CommandGroup>
                    <CommandSeparator />
                    <CommandGroup v-if="countSelected > 0">
                        <CommandItem :value="'_clear_'" class="justify-center text-center" @select="clearFilters">
                            Limpar filtros
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
<script lang="ts" setup>
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem, CommandSeparator } from '@/components/ui/command'

import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { CheckIcon, PlusCircleIcon } from 'lucide-vue-next';
import { cn } from '../../../lib/utils';

interface Option {
    label: string;
    value: string | number;
}

interface FilterProps {
    modelValue: any;
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

const selectedValues = ref<string[]>([]);

const countSelected = computed(() => selectedValues.value.length);

const isSelected = (value: string | number) => {
    return selectedValues.value.includes(value.toString());
};

if (props.modelValue) {
    selectedValues.value = props.modelValue?.split(',');
}
const handleSelect = (option: Option) => {
    if (isSelected(option.value)) {
        selectedValues.value = selectedValues.value.filter(v => v !== option.value.toString());
        if (!selectedValues.value.length) {
            selectedValues.value = [];
        }
    } else {
        selectedValues.value.push(option.value.toString());
    }
    emit('update:modelValue', {
        name: props.filter.name,
        value: selectedValues.value.join(',')
    });
};

const clearFilters = () => {
    selectedValues.value = [];
    emit('update:modelValue', undefined);
};
</script>