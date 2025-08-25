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
                    <CommandGroup>
                        <CommandItem :value="'_clear_'" class="justify-center text-center">
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
    filter: {
        id: string;
        label: string;
        name: string;
        component: any;
        options: Option[];
    };
}
defineProps<FilterProps>();

const modelValue = defineModel<any>('modelValue');

const emit = defineEmits(['update:modelValue']);

const selectedValues = ref<string[]>([]);

const countSelected = computed(() => selectedValues.value.length);

const isSelected = (value: string | number) => {
    return selectedValues.value.includes(value.toString());
};

if (modelValue.value) {
    selectedValues.value = modelValue.value?.split(',');
} else {
    selectedValues.value = [];
}
const handleSelect = (option: Option) => {
    if (isSelected(option.value)) {
        selectedValues.value = selectedValues.value.filter(v => v !== option.value.toString());
    } else {
        selectedValues.value.push(option.value.toString());
    }
    emit('update:modelValue', selectedValues.value);
};

</script>