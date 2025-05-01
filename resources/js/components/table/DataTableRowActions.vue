<script setup lang="ts">
import type { Row } from '@tanstack/vue-table'
import type { TableData } from './schema' 
import { EllipsisVerticalIcon } from 'lucide-vue-next'

// Props para ações
interface Action {
    label: string;
    icon?: any;
    onClick: (row: TableData) => void;
    shortcut?: string;
    danger?: boolean;
}

interface DataTableRowActionsProps {
    row: Row<TableData>;
    actions: Action[];
}

const props = withDefaults(defineProps<DataTableRowActionsProps>(), {
    actions: () => [],
})

const handleAction = (action: Action) => {
    action.onClick(props.row.original);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="flex h-8 w-8 p-0 data-[state=open]:bg-muted">
                <EllipsisVerticalIcon class="h-4 w-4" />
                <span class="sr-only">Abrir menu</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[160px]">
            <template v-for="(action, index) in actions" :key="index">
                <DropdownMenuItem
                    :class="{ 'text-red-600': action.danger }"
                    @click="handleAction(action)"
                >
                    <component v-if="action.icon" :is="action.icon" class="mr-2 h-4 w-4" />
                    {{ action.label }}
                    <DropdownMenuShortcut v-if="action.shortcut">{{ action.shortcut }}</DropdownMenuShortcut>
                </DropdownMenuItem>
                <DropdownMenuSeparator v-if="index < actions.length - 1" />
            </template>
            
            <!-- Fallback quando não há ações definidas -->
            <DropdownMenuItem v-if="!actions.length">
                Nenhuma ação disponível
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>