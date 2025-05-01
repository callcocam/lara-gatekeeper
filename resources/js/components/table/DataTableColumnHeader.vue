<script setup lang="ts">
import type { Column } from '@tanstack/vue-table'
import { cn } from '../../lib/utils' 
import { ArrowDownNarrowWideIcon, ArrowUpNarrowWideIcon, ChevronsUpDown, EyeOffIcon } from 'lucide-vue-next'
interface DataTableColumnHeaderProps {
    column: Column<any>
    title: string
}

defineProps<DataTableColumnHeaderProps>()
</script>

<script lang="ts">
export default {
    inheritAttrs: false,
}
</script>

<template>
    <div v-if="column.getCanSort()" :class="cn('flex items-center space-x-2', $attrs.class ?? '')">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm" class="-ml-3 h-8 data-[state=open]:bg-accent">
                    <span>{{ title }}</span>
                    <ArrowDownNarrowWideIcon v-if="column.getIsSorted() === 'desc'" class="ml-2 h-4 w-4" />
                    <ArrowUpNarrowWideIcon v-else-if="column.getIsSorted() === 'asc'" class="ml-2 h-4 w-4" />
                    <ChevronsUpDown v-else class="ml-2 h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <DropdownMenuItem @click="column.toggleSorting(false)">
                    <ArrowUpNarrowWideIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                    Asc
                </DropdownMenuItem>
                <DropdownMenuItem @click="column.toggleSorting(true)">
                    <ArrowDownNarrowWideIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                    Desc
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="column.toggleVisibility(false)">
                    <EyeOffIcon class="mr-2 h-3.5 w-3.5 text-muted-foreground/70" />
                    Hide
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <div v-else :class="$attrs.class">
        {{ title }}
    </div>
</template>