<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { ChevronLeftIcon, ChevronRightIcon, ArrowLeftIcon, ArrowRightIcon } from 'lucide-vue-next'

interface ServerSideDataTablePaginationProps {
    pageIndex: number
    pageSize: number
    pageCount: number
    totalItems: number
    hasPreviousPage: boolean
    hasNextPage: boolean
}

const props = defineProps<ServerSideDataTablePaginationProps>()

const emit = defineEmits<{
    (e: 'pageChange', page: number): void
    (e: 'pageSizeChange', size: number): void
}>()

const goToPage = (page: number) => {
    emit('pageChange', page)
}

// Manipular a mudança de tamanho de página
const handlePageSizeChange = (value: any) => {
    if (value && !isNaN(Number(value))) {
        emit('pageSizeChange', Number(value))
    }
}
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="flex-1 text-sm text-muted-foreground">
            Mostrando {{ (pageIndex - 1) * pageSize + 1 }} a {{ Math.min(pageIndex * pageSize, totalItems) }} 
            de {{ totalItems }} registro(s).
        </div>
        <div class="flex items-center space-x-6 lg:space-x-8">
            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium">
                    Linhas por página
                </p>
                <Select :model-value="`${pageSize}`" @update:model-value="handlePageSizeChange">
                    <SelectTrigger class="h-8 w-[70px]">
                        <SelectValue :placeholder="`${pageSize}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem v-for="size in [10, 20, 30, 40, 50]" :key="size" :value="`${size}`">
                            {{ size }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex w-[100px] items-center justify-center text-sm font-medium">
                Página {{ pageIndex }} de {{ pageCount }}
            </div>
            <div class="flex items-center space-x-2">
                <Button 
                    variant="outline" 
                    class="hidden h-8 w-8 p-0 lg:flex" 
                    :disabled="!hasPreviousPage"
                    @click="goToPage(1)"
                >
                    <span class="sr-only">Ir para primeira página</span>
                    <ArrowLeftIcon class="h-4 w-4" />
                </Button>
                <Button 
                    variant="outline" 
                    class="h-8 w-8 p-0" 
                    :disabled="!hasPreviousPage"
                    @click="goToPage(pageIndex - 1)"
                >
                    <span class="sr-only">Ir para página anterior</span>
                    <ChevronLeftIcon class="h-4 w-4" />
                </Button>
                <Button 
                    variant="outline" 
                    class="h-8 w-8 p-0" 
                    :disabled="!hasNextPage"
                    @click="goToPage(pageIndex + 1)"
                >
                    <span class="sr-only">Ir para próxima página</span>
                    <ChevronRightIcon class="h-4 w-4" />
                </Button>
                <Button 
                    variant="outline" 
                    class="hidden h-8 w-8 p-0 lg:flex" 
                    :disabled="!hasNextPage"
                    @click="goToPage(pageCount)"
                >
                    <span class="sr-only">Ir para última página</span>
                    <ArrowRightIcon class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template> 