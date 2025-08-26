<template>
    <Badge :variant="getBadgeVariant()" :class="getBadgeClasses()">
        {{ getStatusLabel() }}
    </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Badge, type BadgeVariants } from '@/components/ui/badge';
import { TableCellProps } from '../../../types/tables';

const props = defineProps<TableCellProps>();

// Obtém o valor atual do status
const currentValue = computed(() => props.item[props.column.accessorKey]);

// Encontra a opção correspondente ao valor atual
const statusOption = computed(() => {
    if (!props.column.options || !Array.isArray(props.column.options)) {
        return null;
    }
    
    return props.column.options.find((option: any) => 
        option.value === currentValue.value || option.value === String(currentValue.value)
    );
});

// Retorna o label do status (traduzido ou o valor original)
const getStatusLabel = () => {
    return statusOption.value?.label || currentValue.value || 'N/A';
};

// Mapeia cores dos Enums PHP para variantes do Badge
const getColorVariant = (color: string): BadgeVariants['variant'] => {
    const colorMap: Record<string, BadgeVariants['variant']> = {
        'green': 'default',
        'blue': 'secondary', 
        'gray': 'outline',
        'red': 'destructive',
        'yellow': 'secondary'
    };
    
    return colorMap[color] || 'outline';
};

// Retorna a variante do badge
const getBadgeVariant = (): BadgeVariants['variant'] => {
    if (!statusOption.value?.color) {
        return 'outline';
    }
    
    return getColorVariant(statusOption.value.color);
};

// Classes CSS customizadas baseadas na cor
const getBadgeClasses = () => {
    if (!statusOption.value?.color) {
        return 'text-xs font-normal';
    }
    
    const colorClasses: Record<string, string> = {
        'green': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'blue': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'gray': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        'red': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'yellow': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    };
    
    const baseClass = 'text-xs font-normal border-0';
    const colorClass = colorClasses[statusOption.value.color] || 'bg-gray-100 text-gray-800';
    
    return `${baseClass} ${colorClass}`;
};
</script>