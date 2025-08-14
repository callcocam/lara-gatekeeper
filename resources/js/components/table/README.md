# SimpleDataTable - Tabela sem TanStack

Uma implementação de tabela de dados simples e eficiente usando apenas Vue 3 e componentes shadcn-vue, sem dependência do TanStack Table.

## Características

- ✅ **Busca em tempo real** com debounce
- ✅ **Filtros facetados** (multi-seleção) 
- ✅ **Ordenação de colunas** com indicadores visuais
- ✅ **Paginação completa** com controles
- ✅ **Visibilidade de colunas** configurável
- ✅ **URL sincronizada** com todos os parâmetros
- ✅ **Estado persistente** ao navegar
- ✅ **Totalmente responsiva**
- ✅ **Componentes shadcn-vue** para consistência visual

## Uso Básico

```vue
<template>
  <SimpleDataTable
    :data="data"
    :columns="columns"
    :filters="filters"
    :initial-search="search"
    :initial-sort="sort"
    :initial-direction="direction"
    :initial-filters="filters"
    current-route="admin.products.index"
  />
</template>

<script setup>
import SimpleDataTable from '@/packages/callcocam/lara-gatekeeper/resources/js/components/table/SimpleDataTable.vue'

// Dados vindos do backend (Laravel)
const props = defineProps({
  data: Object, // PaginatedData
  columns: Array, // SimpleColumn[]
  filters: Object, // Record<string, string>
  search: String,
  sort: String,
  direction: String,
})
</script>
```

## Definindo Colunas

```typescript
const columns: SimpleColumn[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    sortable: true
  },
  {
    accessorKey: 'name', 
    header: 'Nome',
    sortable: true
  },
  {
    accessorKey: 'status',
    header: 'Status',
    isStatus: true, // Renderiza como StatusBadge
    sortable: true
  },
  {
    id: 'actions',
    header: 'Ações'
  }
]
```

## Definindo Filtros

```typescript
const filters: Filter[] = [
  {
    title: 'Status',
    column: 'status',
    type: 'faceted',
    options: [
      { label: 'Ativo', value: 'active' },
      { label: 'Inativo', value: 'inactive' }
    ]
  },
  {
    title: 'Categoria',
    column: 'category',
    type: 'faceted', 
    options: [
      { label: 'Eletrônicos', value: 'electronics' },
      { label: 'Roupas', value: 'clothing' }
    ]
  }
]
```

## Usando o Composable

```typescript
import { useSimpleDataTableColumns } from '@/composables/useSimpleDataTableColumns'

const { tableColumns } = useSimpleDataTableColumns({
  columns: computed(() => props.columns),
  routeNameBase: computed(() => 'admin.products'),
  deleteItem: handleDelete,
  actions: [] // Ações personalizadas
})
```

## Funcionalidades

### 1. Busca
- Busca em tempo real com debounce de 400ms
- Sincronizada com URL (`?search=termo`)
- Redefine para página 1 automaticamente

### 2. Filtros Facetados
- Multi-seleção com checkboxes
- Popover com busca interna
- Badges para valores selecionados
- Remoção individual de filtros
- Sincronizado com URL (`?status=active,inactive`)

### 3. Ordenação
- Clique no cabeçalho para ordenar
- Três estados: ASC → DESC → LIMPO
- Indicadores visuais (setas)
- Sincronizada com URL (`?sort=name&direction=asc`)

### 4. Paginação
- Controles completos (primeira, anterior, próxima, última)
- Seletor de itens por página
- Informações de registros
- Sincronizada com URL (`?page=2&per_page=20`)

### 5. Visibilidade de Colunas
- Dropdown para mostrar/ocultar colunas
- Estado persistente durante navegação
- Exclui automaticamente colunas de ações

## Parâmetros da URL

A tabela sincroniza automaticamente todos os parâmetros com a URL:

```
/admin/products?search=test&status=active,published&sort=name&direction=asc&page=2&per_page=20
```

### Remoção de Filtros
- Filtros vazios são **removidos completamente** da URL
- Não deixa parâmetros vazios (`?status=`)
- Navegação limpa e URLs amigáveis

## Vantagens sobre TanStack

1. **Simplicidade**: Código mais simples e direto
2. **Performance**: Menos overhead, renderização mais rápida
3. **Manutenibilidade**: Easier debugging e customização
4. **Bundle Size**: Menor tamanho final
5. **Flexibilidade**: Total controle sobre comportamento
6. **URL Handling**: Gerenciamento direto e confiável de parâmetros

## Exemplo Completo

Veja os arquivos de exemplo:
- `resources/js/pages/users/SimpleIndex.vue`
- `resources/js/pages/admin/products/SimpleIndex.vue`

## Backend (Laravel)

O backend deve retornar dados no formato:

```php
return [
    'data' => $items->items(),
    'meta' => [
        'current_page' => $items->currentPage(),
        'from' => $items->firstItem(),
        'last_page' => $items->lastPage(),
        'per_page' => $items->perPage(),
        'to' => $items->lastItem(),
        'total' => $items->total(),
    ],
    'links' => [
        'prev' => $items->previousPageUrl(),
        'next' => $items->nextPageUrl(),
    ]
];
```
