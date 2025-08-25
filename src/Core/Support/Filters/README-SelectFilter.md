# SelectFilter Melhorado

O `SelectFilter` foi completamente reescrito para oferecer uma experiência mais rica e flexível.

## 🚀 Novas Funcionalidades

### 🔧 Backend (PHP)

#### 1. **Suporte a Relacionamentos Automáticos**
```php
SelectFilter::make('category_id', 'Categoria')
    ->relationship('category', 'name', 'id');
```
- Carrega automaticamente opções do relacionamento
- Aplica filtros usando `whereHas()`
- Suporte para colunas customizadas

#### 2. **Filtros Inteligentes**
- Detecta automaticamente se o valor é array ou string
- Normaliza strings separadas por vírgula para arrays
- Aplica `whereIn()` para múltiplos valores
- Aplica `where()` para valores únicos

#### 3. **Formatação Customizada**
```php
SelectFilter::make('status', 'Status')
    ->formatUsing(function ($query, $value) {
        // Lógica customizada
    });
```

### 🎨 Frontend (Vue)

#### 1. **Interface Melhorada**
- **Badges de seleção** com opção de remoção individual
- **Contador visual** de itens selecionados
- **Busca integrada** com placeholder dinâmico
- **Feedback visual** melhorado

#### 2. **Suporte Completo a Múltipla Seleção**
- Checkbox visual para seleções
- Badges individuais para cada item selecionado
- Botão X para remover itens específicos
- Contadores de seleção

#### 3. **UX Aprimorada**
- Popover fecha automaticamente em seleção única
- Mantém aberto para seleção múltipla
- Placeholder contextual para busca
- Mensagens em português

## 📋 Como Usar

### Filtro Simples (Seleção Única)
```php
SelectFilter::make('status', 'Status')
    ->options([
        ['label' => 'Ativo', 'value' => 'active'],
        ['label' => 'Inativo', 'value' => 'inactive'],
    ]);
```

### Filtro Múltiplo
```php
SelectFilter::make('tags', 'Tags')
    ->multiple()
    ->options([
        ['label' => 'Importante', 'value' => 'important'],
        ['label' => 'Urgente', 'value' => 'urgent'],
    ]);
```

### Filtro com Relacionamento
```php
SelectFilter::make('category_id', 'Categoria')
    ->relationship('category', 'name', 'id');
```

### Filtro com Contadores
```php
SelectFilter::make('priority', 'Prioridade')
    ->options([
        ['label' => 'Alta', 'value' => 'high', 'count' => 15],
        ['label' => 'Média', 'value' => 'medium', 'count' => 30],
    ]);
```

## 🎯 Comportamento

### Seleção Única:
1. **Primeiro clique**: Seleciona o item
2. **Segundo clique**: Deseleciona o item
3. **Popover fecha** automaticamente após seleção

### Seleção Múltipla:
1. **Clique**: Adiciona/remove da seleção
2. **Badge X**: Remove item específico
3. **"Limpar seleção"**: Remove todos os itens
4. **Popover permanece aberto** para múltiplas seleções

## 🔍 Busca

- Campo de busca integrado
- Placeholder dinâmico: "Buscar categoria..."
- Filtra opções em tempo real
- Mantém estado de seleção

## 📊 Contadores

Suporte opcional para mostrar quantos registros existem para cada opção:

```php
['label' => 'Ativo', 'value' => 'active', 'count' => 15]
```

Aparece como um pequeno número à direita da opção.

## 🎨 Interface Visual

- **Badges secundárias** para itens selecionados
- **Separador visual** entre label e badges
- **Contador compacto** no mobile (lg:hidden)
- **Badges expandidas** no desktop
- **Hover effects** nos botões de remoção
- **Icons contextuais** (Plus, Check, X)

## 🔧 Extensibilidade

O filtro pode ser facilmente estendido:

1. **Sobrescrever `getModelClass()`** para relacionamentos automáticos
2. **Customizar `formatUsing()`** para lógica específica
3. **Adicionar propriedades** no `toArray()` para dados extras
4. **Estender o componente Vue** para funcionalidades específicas
