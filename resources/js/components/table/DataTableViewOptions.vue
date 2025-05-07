<script setup lang="ts">
import type { Table } from '@tanstack/vue-table'
import { computed, onMounted, watch } from 'vue'
import { Settings2 } from 'lucide-vue-next'
 

interface DataTableViewOptionsProps {
    table: Table<any>
    tableName?: string
}

const props = defineProps<DataTableViewOptionsProps>()

const visibilityStorageKey = computed(() => props.tableName ? `table_visibility_${props.tableName}` : null)

const columns = computed(() => props.table.getAllColumns()
    .filter(
        column => column.getCanHide(),
    ))

// Lógica para carregar/salvar visibilidade (opcional)
onMounted(() => {
    if (visibilityStorageKey.value) {
        const storedVisibility = localStorage.getItem(visibilityStorageKey.value);
        if (storedVisibility) {
            try {
                props.table.setColumnVisibility(JSON.parse(storedVisibility));
            } catch (e) {
                console.error("Erro ao parsear visibilidade do localStorage:", e);
                // Opcional: remover item inválido
                // localStorage.removeItem(visibilityStorageKey.value);
            }
        }
    }
});

watch(() => props.table.getState().columnVisibility, (newVisibility) => {
    if (visibilityStorageKey.value) {
        localStorage.setItem(visibilityStorageKey.value, JSON.stringify(newVisibility));
    }
}, { deep: true });

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="ml-auto hidden h-8 lg:flex">
                <Settings2 class="mr-2 h-4 w-4" />
                Colunas
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[150px]">
            <DropdownMenuLabel>Alternar Colunas</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem v-for="column in columns" :key="column.id" class="capitalize"
                :modelValue="column.getIsVisible()"
                @update:model-value="(value: boolean) => props.table.setColumnVisibility(old => ({ ...old, [column.id]: !!value }))">
                {{ typeof column.columnDef.header === 'string' ? column.columnDef.header : column.id }}
            </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>