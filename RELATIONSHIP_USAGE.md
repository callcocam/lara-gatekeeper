# Sistema de Relacionamentos Dinâmicos - Lara Gatekeeper

## Visão Geral

Este sistema permite configurar relacionamentos dinamicamente para filtros, opções e buscas, com detecção automática e configuração manual.

## Traits Disponíveis

### 1. RelationshipDetector
Detecta relacionamentos automaticamente e permite configuração manual.

### 2. RelationshipFilter  
Cria filtros baseados em relacionamentos com `whereHas`, `whereDoesntHave`.

### 3. BelongsToOptions (Refatorado)
Integra com os novos traits e mantém compatibilidade.

### 4. RelationshipManager
Trait principal que combina todos os outros para uso simplificado.

### 5. CascadingFields
Trait para gerenciar campos dependentes (cascading dropdowns) onde cada nível depende do anterior.

## Exemplos de Uso

### Configuração Básica

```php
use Callcocam\LaraGatekeeper\Core\Concerns\RelationshipManager;

class ProductFilter
{
    use RelationshipManager;

    public function configure()
    {
        // Configuração manual
        $this->relationship('category', 'name', 'id');
        
        // Ou usando o novo método
        $this->viaRelationship('category', 'name', 'id');
        
        // Configuração com filtro
        $this->withRelationship('category', 'name', 'id', function($query) {
            return $query->where('active', true);
        });
    }
}
```

### Filtros Dinâmicos

```php
// Filtro simples
$this->whereRelationship('category', 'id', request('category_id'));

// Filtro com LIKE
$this->whereRelationshipLike('category', 'name', request('search'));

// Filtro customizado
$this->whereRelationshipCallback('category', function($query) {
    return $query->where('active', true)
                 ->where('featured', true);
});

// Múltiplos relacionamentos
$this->filterByMultipleRelationships([
    'category' => ['callback' => fn($q) => $q->where('active', true)],
    'brand' => ['callback' => fn($q) => $q->where('premium', true)],
    'tags' => function($q) { return $q->where('type', 'product'); }
]);
```

### Busca Automática

```php
// Busca por nome da categoria
$this->searchByRelationship('category', 'name', 'eletrônicos');

// Aplicar em query
$query = Product::query();
$query = $this->applyToQuery($query, new Product());
```

### Campos Dependentes (Cascading Dropdowns)

```php
// Configuração básica
$this->setBaseModel(\App\Models\Category::class)
     ->setColumns('category_id', 'name', 'id');

$this->cascadingFields([
    'departamento' => 'segmento_varejista',
    'subdepartamento' => 'departamento',
    'categoria' => 'subdepartamento',
    'subcategoria' => 'categoria',
    'segmento' => 'subcategoria',
    'subsegmento' => 'segmento'
]);

// Obtém opções para um campo específico
$departamentos = $this->getCascadingOptions('departamento');

// Obtém todas as opções
$allOptions = $this->getAllCascadingOptions();
```

### Detecção Automática

```php
// Detecta todos os relacionamentos do modelo
$relationships = $this->detectRelationships(new Product());

// Verifica se um relacionamento existe
if ($this->relationshipExists(new Product(), 'category')) {
    // Faz algo
}

// Obtém o tipo de relacionamento
$type = $this->getRelationshipType(new Product(), 'category');
// Retorna: HasMany, BelongsTo, etc.
```

## Métodos Principais

### RelationshipManager

- `withRelationship()` - Configuração rápida com filtro
- `searchByRelationship()` - Busca automática
- `filterByMultipleRelationships()` - Múltiplos filtros
- `applyToQuery()` - Aplica filtros em query
- `getAllRelationshipOptions()` - Todas as opções
- `resetRelationships()` - Limpa configurações
- `getRelationshipStats()` - Estatísticas

### RelationshipDetector

- `relationship()` - Configuração manual
- `detectRelationships()` - Detecção automática
- `relationshipExists()` - Verifica existência
- `getRelationshipType()` - Tipo do relacionamento

### RelationshipFilter

- `filterByRelationship()` - Filtro customizado
- `whereRelationship()` - Filtro simples
- `whereRelationshipIn()` - Filtro IN
- `whereRelationshipLike()` - Filtro LIKE
- `whereDoesntHaveRelationship()` - Relacionamento não existente

### CascadingFields

- `cascadingField()` - Configura campo dependente
- `cascadingFields()` - Configura múltiplos campos
- `getCascadingOptions()` - Obtém opções de campo específico
- `getAllCascadingOptions()` - Obtém todas as opções
- `getFieldHierarchy()` - Obtém hierarquia completa
- `getRootFields()` - Obtém campos de nível raiz
- `getChildFields()` - Obtém campos filhos

## Compatibilidade

O sistema mantém compatibilidade com o código existente:

```php
// Código antigo ainda funciona
$this->options($model);

// Novos métodos disponíveis
$this->viaRelationship('category', 'name', 'id');
$this->whereRelationship('category', 'id', request('category_id'));
```

## Casos de Uso Comuns

1. **Filtros de Produtos por Categoria**
2. **Busca de Usuários por Departamento**
3. **Filtros de Pedidos por Cliente**
4. **Relatórios com Múltiplos Relacionamentos**
5. **APIs com Filtros Dinâmicos**

## Performance

- Relacionamentos são detectados uma vez por instância
- Filtros são aplicados apenas quando necessário
- Suporte a eager loading para otimização
- Cache de relacionamentos detectados 