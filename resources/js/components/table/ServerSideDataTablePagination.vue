<script setup lang="ts">
import { computed } from 'vue' 
import { ChevronLeftIcon, ChevronRightIcon, ArrowLeftIcon, ArrowRightIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'

interface ServerSideDataTablePaginationProps {
    pageIndex: number // Current page (1-based)
    pageSize: number
    pageCount: number // Total number of pages
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
    // Garante que a página está dentro dos limites
    const validPage = Math.max(1, Math.min(page, props.pageCount));
    if (validPage !== props.pageIndex) { // Evita emitir se a página já for a atual
      console.log(`[Gatekeeper/Pagination] Emitting pageChange: ${validPage}`);
      emit('pageChange', validPage);
    }
}

const handlePageSizeChange = (value: string | number) => {
    const newSize = Number(value);
    if (!isNaN(newSize) && newSize > 0 && newSize !== props.pageSize) {
        console.log(`[Gatekeeper/Pagination] Emitting pageSizeChange: ${newSize}`);
        emit('pageSizeChange', newSize);
    }
}

// Calculando o range de itens exibidos
const fromItem = computed(() => {
    if (props.totalItems === 0) return 0;
    return (props.pageIndex - 1) * props.pageSize + 1;
});
const toItem = computed(() => {
    return Math.min(props.pageIndex * props.pageSize, props.totalItems);
});

</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="flex-1 text-sm text-muted-foreground">
            <template v-if="totalItems > 0">
                Mostrando {{ fromItem }} a {{ toItem }}
                de {{ totalItems }} registro(s).
            </template>
            <template v-else>
                Nenhum registro encontrado.
            </template>
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
            <div class="flex w-[110px] items-center justify-center text-sm font-medium">
                 <template v-if="pageCount > 0">
                    Página {{ pageIndex }} de {{ pageCount }}
                 </template>
                 <template v-else>
                    Página 0 de 0
                 </template>
            </div>
            <div class="flex items-center space-x-2">
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="!hasPreviousPage"
                    @click="goToPage(1)"
                    aria-label="Ir para primeira página"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!hasPreviousPage"
                    @click="goToPage(pageIndex - 1)"
                     aria-label="Ir para página anterior"
                >
                    <ChevronLeftIcon class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="h-8 w-8 p-0"
                    :disabled="!hasNextPage"
                    @click="goToPage(pageIndex + 1)"
                     aria-label="Ir para próxima página"
                >
                    <ChevronRightIcon class="h-4 w-4" />
                </Button>
                <Button
                    variant="outline"
                    class="hidden h-8 w-8 p-0 lg:flex"
                    :disabled="!hasNextPage"
                    @click="goToPage(pageCount)"
                     aria-label="Ir para última página"
                >
                    <ArrowRightIcon class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template> 