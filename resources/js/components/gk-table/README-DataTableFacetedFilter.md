# DataTableFacetedFilter

Um componente de filtro facetado para **seleção múltipla** em tabelas, sem dependência do TanStack. Interface intuitiva com checkboxes e badges.

## Características

- ✅ **Seleção múltipla apenas** - Focado em filtros com múltiplas opções
- ✅ **Interface com checkboxes** - Visual claro e intuitivo
- ✅ **Badges de seleção** - Mostra itens selecionados com opção de remoção individual
- ✅ **Contador de resultados** - Exibe quantos itens existem para cada opção
- ✅ **Busca integrada** - Campo de busca dentro do popover
- ✅ **Limpar filtros** - Botão para remover todas as seleções
- ✅ **TypeScript** - Totalmente tipado

## Uso Básico

```vue
<template>
    <DataTableFacetedFilter
        v-model="selectedValues"
        :filter="filterConfig"
        @update:modelValue="handleFilterChange"
    />
</template>

<script setup>
import DataTableFacetedFilter from './filters/DataTableFacetedFilter.vue'

const selectedValues = ref(undefined) // string[] | undefined

const filterConfig = {
    id: 'status',
    label: 'Status',
    name: 'status',
    component: DataTableFacetedFilter,
    options: [
        {
            label: 'Ativo',
            value: 'active',
            count: 15 // opcional
        },
        {
            label: 'Inativo',
            value: 'inactive',
            count: 5
        }
    ]
}

const handleFilterChange = (value) => {
    console.log('Filtro mudou:', value)
    // Retorna: ['active', 'inactive'] ou undefined
}
</script>
```

## Props

| Prop | Tipo | Descrição |
|------|------|-----------|
| `modelValue` | `string[] \| number[] \| undefined` | Valores selecionados |
| `filter` | `FilterConfig` | Configuração do filtro |

## Interface FilterConfig

```typescript
interface FilterConfig {
    id: string;          // ID único do filtro
    label: string;       // Rótulo exibido no botão
    name: string;        // Nome usado para identificar o filtro
    component: any;      // Componente (DataTableFacetedFilter)
    options: Option[];   // Opções disponíveis
}

interface Option {
    label: string;              // Texto exibido
    value: string | number;     // Valor da opção
    icon?: any;                // Ícone opcional (componente)
    count?: number;            // Contador opcional
}
```

## Exemplos de Configuração

### Filtro de Status
```typescript
const statusFilter = {
    id: 'status',
    label: 'Status',
    name: 'status',
    component: DataTableFacetedFilter,
    options: [
        { label: 'Ativo', value: 'active', count: 15 },
        { label: 'Inativo', value: 'inactive', count: 5 },
        { label: 'Pendente', value: 'pending', count: 3 }
    ]
}
```

### Filtro de Categoria
```typescript
const categoryFilter = {
    id: 'category',
    label: 'Categoria', 
    name: 'category',
    component: DataTableFacetedFilter,
    options: [
        { label: 'Tecnologia', value: 'tech', count: 12 },
        { label: 'Marketing', value: 'marketing', count: 8 },
        { label: 'Vendas', value: 'sales', count: 10 }
    ]
}
```

## Integração com GtDataTableToolbar

```vue
<template>
    <div class="flex items-center justify-between p-4 border-b">
        <div class="flex items-center space-x-2">
            <FilterRenderer 
                v-for="filter in filters" 
                :key="filter.id" 
                :filter="filter"
                @update:modelValue="updateFilterValue" 
            />
        </div>
        <!-- resto do template -->
    </div>
</template>

<script setup>
import DataTableFacetedFilter from './filters/DataTableFacetedFilter.vue'

const filters = [
    {
        id: 'status',
        label: 'Status',
        name: 'status',
        component: DataTableFacetedFilter,
        options: [
            { label: 'Ativo', value: 'active', count: 15 },
            { label: 'Inativo', value: 'inactive', count: 5 }
        ]
    }
]
</script>
```

## Eventos

- `update:modelValue` - Emitido quando a seleção muda
  - Retorna: `string[]` (array de valores) ou `undefined` (quando vazio)

## Comportamento

1. **Seleção**: Clique em uma opção para adicionar/remover da seleção
2. **Badges**: Itens selecionados aparecem como badges no botão
3. **Remoção individual**: Hover sobre o badge para ver o botão X
4. **Limpar tudo**: Botão "Limpar filtros" remove todas as seleções
5. **Busca**: Digite no campo para filtrar as opções disponíveis

## Estilos

O componente usa classes Tailwind CSS e componentes shadcn/ui:
- `Button`, `Badge`, `Separator` 
- `Popover`, `PopoverTrigger`, `PopoverContent`
- `Command`, `CommandInput`, `CommandList`, etc.

## Dependências

- Vue 3 Composition API
- shadcn/ui components
- Tailwind CSS
- lucide-vue-next (ícones)
