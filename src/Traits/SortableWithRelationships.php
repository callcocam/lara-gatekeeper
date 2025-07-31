<?php

namespace Callcocam\LaraGatekeeper\Traits;

trait SortableWithRelationships
{
    /**
     * Aplica ordenação incluindo suporte para relacionamentos
     */
    public function applySorting($query, $request, array $tableColumns)
    {
        try {
            if ($request->has('sort')) {
                $direction = $request->input('direction', 'asc');
                $columnKey = $request->input('sort');

                $sortColumnDef = $this->findColumnDefinition($columnKey, $tableColumns);

                if ($sortColumnDef && ($sortColumnDef['sortable'] ?? false)) {
                    $dbColumn = $sortColumnDef['accessorKey'] ?? $sortColumnDef['id'] ?? null;

                    if ($dbColumn) {
                        // Preservar condições WHERE existentes antes de aplicar JOIN
                        $this->preserveExistingConditions($query);
                        $this->applyColumnSorting($query, $dbColumn, $direction);
                    }
                }
            } else {
                $this->applyDefaultSorting($query, $tableColumns);
            }
        } catch (\Exception $e) {
            // Log do erro e aplicar ordenação padrão
            if (function_exists('logger')) {
                logger()->warning('Erro ao aplicar ordenação', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Fallback seguro
            $query->latest();
        }

        return $query;
    }

    /**
     * Preserva condições WHERE existentes qualificando colunas ambíguas
     */
    private function preserveExistingConditions($query): void
    {
        $mainTable = $this->getMainTableName($query);
        $this->qualifyAmbiguousColumns($query, $mainTable);
    }

    /**
     * Encontra a definição da coluna
     */
    private function findColumnDefinition(string $columnKey, array $tableColumns): ?array
    {
        foreach ($tableColumns as $colDef) {
            if (($colDef['accessorKey'] ?? $colDef['id'] ?? null) === $columnKey) {
                return $colDef;
            }
        }
        return null;
    }

    /**
     * Aplica ordenação para uma coluna específica
     */
    private function applyColumnSorting($query, string $dbColumn, string $direction): void
    {
        try {
            if (str_contains($dbColumn, '.')) {
                $this->applySortingWithRelationship($query, $dbColumn, $direction);
            } else {
                $query->orderBy($dbColumn, $direction);
            }
        } catch (\Exception $e) {
            // Log do erro para debug
            if (function_exists('logger')) {
                logger()->warning('Erro ao aplicar ordenação', [
                    'column' => $dbColumn,
                    'direction' => $direction,
                    'error' => $e->getMessage()
                ]);
            }

            // Fallback para ordenação padrão
            $query->latest();
        }
    }

    /**
     * Aplica ordenação padrão
     */
    private function applyDefaultSorting($query, array $tableColumns): void
    {
        $defaultSortColumn = $this->getTableDefaultSortColumn() ?? $this->findFirstSortableColumn($tableColumns);

        if ($defaultSortColumn) {
            $this->applyColumnSorting($query, $defaultSortColumn, 'asc');
        } else {
            $query->latest();
        }
    }

    /**
     * Encontra a primeira coluna ordenável
     */
    private function findFirstSortableColumn(array $tableColumns): ?string
    {
        foreach ($tableColumns as $colDef) {
            if ($colDef['sortable'] ?? false) {
                return $colDef['accessorKey'] ?? $colDef['id'] ?? null;
            }
        }
        return null;
    }

    /**
     * Aplica ordenação para colunas de relacionamento
     */
    private function applySortingWithRelationship($query, string $relationColumn, string $direction = 'asc'): void
    {
        $parts = explode('.', $relationColumn);

        if (count($parts) < 2) {
            return;
        }

        // Para relacionamentos simples (ex: category.name)
        if (count($parts) === 2) {
            $this->applySingleRelationshipSort($query, $parts[0], $parts[1], $direction);
        } else {
            // Para relacionamentos aninhados (ex: category.parent.name)
            $this->applyNestedRelationshipSort($query, $parts, $direction);
        }
    }

    /**
     * Aplica ordenação para relacionamento simples
     */
    private function applySingleRelationshipSort($query, string $relationName, string $columnName, string $direction): void
    {
        $mapping = $this->getRelationshipMapping($relationName);
        $joinAlias = $mapping['table'] . '_sort'; // Usar alias único para evitar conflitos

        // Verificar se é uma coluna virtual/calculada que precisa de tratamento especial
        if ($this->isVirtualColumn($relationName, $columnName)) {
            $this->applyVirtualColumnSort($query, $relationName, $columnName, $direction);
            return;
        }

        // Verificar se o JOIN já existe
        if (!$this->hasJoin($query, $joinAlias)) {
            $mainTable = $this->getMainTableName($query);

            $query->leftJoin(
                $mapping['table'] . ' as ' . $joinAlias,
                $mainTable . '.' . $mapping['foreign_key'],
                '=',
                $joinAlias . '.' . $mapping['local_key']
            );

            // Garantir que selecionamos apenas as colunas da tabela principal
            $this->ensureMainTableSelection($query, $mainTable);
        }

        $query->orderBy($joinAlias . '.' . $columnName, $direction);
    }

    /**
     * Aplica ordenação para relacionamentos aninhados
     */
    private function applyNestedRelationshipSort($query, array $parts, string $direction): void
    {
        $mainTable = $this->getMainTableName($query);
        $currentTable = $mainTable;
        $joinedTables = [];

        // Processar cada nível do relacionamento
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $relationName = $parts[$i];
            $mapping = $this->getRelationshipMapping($relationName);
            $joinAlias = $mapping['table'] . '_' . $i;

            if (!in_array($joinAlias, $joinedTables)) {
                $query->leftJoin(
                    $mapping['table'] . ' as ' . $joinAlias,
                    $currentTable . '.' . $mapping['foreign_key'],
                    '=',
                    $joinAlias . '.' . $mapping['local_key']
                );

                $joinedTables[] = $joinAlias;
            }

            $currentTable = $joinAlias;
        }

        // Aplicar ordenação na coluna final
        $finalColumn = end($parts);
        $query->orderBy($currentTable . '.' . $finalColumn, $direction);

        $this->ensureMainTableSelection($query, $mainTable);
    }

    /**
     * Verifica se um JOIN já existe na query
     */
    private function hasJoin($query, string $tableOrAlias): bool
    {
        $joins = $query->getQuery()->joins ?? [];

        foreach ($joins as $join) {
            // Verifica se o nome da tabela ou alias já existe
            if (str_contains($join->table, $tableOrAlias)) {
                return true;
            }
            
            // Verifica também no padrão "table as alias"
            if (preg_match('/\bas\s+' . preg_quote($tableOrAlias, '/') . '\b/i', $join->table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém o nome da tabela principal
     */
    private function getMainTableName($query): string
    {
        // Tentar obter da query
        if (method_exists($query, 'getModel') && $query->getModel()) {
            return $query->getModel()->getTable();
        }

        // Fallback para método da classe
        if (method_exists($this, 'getTableName')) {
            return $this->getTableName();
        }

        // Último recurso - assumir 'products'
        return 'products';
    }

    /**
     * Garante que apenas as colunas da tabela principal sejam selecionadas
     */
    private function ensureMainTableSelection($query, string $mainTable): void
    {
        if (empty($query->getQuery()->columns)) {
            $query->select($mainTable . '.*');
        }
        
        // Garante que colunas ambíguas sejam qualificadas
        $this->qualifyAmbiguousColumns($query, $mainTable);
    }

    /**
     * Qualifica colunas ambíguas para evitar conflitos em JOINs
     */
    private function qualifyAmbiguousColumns($query, string $mainTable): void
    {
        // Lista de colunas que comumente geram ambiguidade
        $ambiguousColumns = ['id', 'created_at', 'updated_at', 'deleted_at', 'status', 'name'];
        
        $queryInstance = $query->getQuery();
        
        // Qualificar WHERE clauses
        if (!empty($queryInstance->wheres)) {
            foreach ($queryInstance->wheres as &$where) {
                $this->qualifyWhereCondition($where, $ambiguousColumns, $mainTable);
            }
        }
        
        // Qualificar WHERE IN especificamente para IDs
        $this->qualifyWhereInConditions($queryInstance, $mainTable);
        
        // Qualificar GROUP BY
        if (!empty($queryInstance->groups)) {
            foreach ($queryInstance->groups as &$group) {
                if (is_string($group) && in_array($group, $ambiguousColumns) && !str_contains($group, '.')) {
                    $group = $mainTable . '.' . $group;
                }
            }
        }
        
        // Qualificar HAVING
        if (!empty($queryInstance->havings)) {
            foreach ($queryInstance->havings as &$having) {
                if (isset($having['column']) && is_string($having['column'])) {
                    $column = $having['column'];
                    if (in_array($column, $ambiguousColumns) && !str_contains($column, '.')) {
                        $having['column'] = $mainTable . '.' . $column;
                    }
                }
            }
        }
    }

    /**
     * Qualifica condições WHERE individuais
     */
    private function qualifyWhereCondition(&$where, array $ambiguousColumns, string $mainTable): void
    {
        if (isset($where['column']) && is_string($where['column'])) {
            $column = $where['column'];
            if (in_array($column, $ambiguousColumns) && !str_contains($column, '.')) {
                $where['column'] = $mainTable . '.' . $column;
            }
        }
        
        // Para condições aninhadas (nested where)
        if (isset($where['query']) && is_callable($where['query'])) {
            // Deixar as consultas aninhadas como estão, pois elas têm seu próprio escopo
        }
    }

    /**
     * Qualifica especificamente condições WHERE IN para IDs
     */
    private function qualifyWhereInConditions($queryInstance, string $mainTable): void
    {
        if (!empty($queryInstance->wheres)) {
            foreach ($queryInstance->wheres as &$where) {
                if (isset($where['type']) && $where['type'] === 'In' && 
                    isset($where['column']) && $where['column'] === 'id') {
                    $where['column'] = $mainTable . '.id';
                }
                
                if (isset($where['type']) && $where['type'] === 'InSub' && 
                    isset($where['column']) && $where['column'] === 'id') {
                    $where['column'] = $mainTable . '.id';
                }
            }
        }
    }

    /**
     * Obtém mapeamento para um relacionamento específico
     */
    private function getRelationshipMapping(string $relationName): array
    {
        $mappings = $this->getRelationshipMappings();

        return $mappings[$relationName] ?? [
            'table' => $this->pluralize($relationName),
            'foreign_key' => $relationName . '_id',
            'local_key' => 'id'
        ];
    }

    /**
     * Mapeamentos de relacionamentos
     */
    private function getRelationshipMappings(): array
    {
        return [
            'category' => [
                'table' => 'categories',
                'foreign_key' => 'category_id',
                'local_key' => 'id'
            ],
            'user' => [
                'table' => 'users',
                'foreign_key' => 'user_id',
                'local_key' => 'id'
            ],
            'client' => [
                'table' => 'clients',
                'foreign_key' => 'client_id',
                'local_key' => 'id'
            ],
            'tenant' => [
                'table' => 'tenants',
                'foreign_key' => 'tenant_id',
                'local_key' => 'id'
            ],
            'parent' => [
                'table' => 'categories',
                'foreign_key' => 'category_id',
                'local_key' => 'id'
            ]
        ];
    }

    /**
     * Verifica se é uma coluna virtual/calculada
     */
    private function isVirtualColumn(string $relationName, string $columnName): bool
    {
        $virtualColumns = $this->getVirtualColumns();
        
        return isset($virtualColumns[$relationName][$columnName]);
    }

    /**
     * Aplica ordenação para colunas virtuais/calculadas
     */
    private function applyVirtualColumnSort($query, string $relationName, string $columnName, string $direction): void
    {
        $virtualColumns = $this->getVirtualColumns();
        $virtualConfig = $virtualColumns[$relationName][$columnName] ?? null;

        if (!$virtualConfig) {
            // Fallback para coluna 'name' se a virtual não estiver configurada
            $this->applySingleRelationshipSort($query, $relationName, 'name', $direction);
            return;
        }

        // Se tem uma coluna real alternativa definida
        if (isset($virtualConfig['fallback_column'])) {
            $this->applySingleRelationshipSort($query, $relationName, $virtualConfig['fallback_column'], $direction);
            return;
        }

        // Se tem uma query personalizada
        if (isset($virtualConfig['custom_sort'])) {
            $this->applyCustomSort($query, $virtualConfig['custom_sort'], $direction);
            return;
        }

        // Fallback padrão
        $this->applySingleRelationshipSort($query, $relationName, 'name', $direction);
    }

    /**
     * Aplica ordenação customizada
     */
    private function applyCustomSort($query, array $customSort, string $direction): void
    {
        $mainTable = $this->getMainTableName($query);
        
        if (isset($customSort['join'])) {
            $join = $customSort['join'];
            
            if (!$this->hasJoin($query, $join['table'])) {
                $query->leftJoin(
                    $join['table'] . ' as ' . $join['alias'],
                    $mainTable . '.' . $join['foreign_key'],
                    '=',
                    $join['alias'] . '.' . $join['local_key']
                );
                
                $this->ensureMainTableSelection($query, $mainTable);
            }
        }

        if (isset($customSort['order_by'])) {
            $query->orderBy($customSort['order_by'], $direction);
        }
    }

    /**
     * Define colunas virtuais e suas configurações
     */
    private function getVirtualColumns(): array
    {
        // Permite que a classe filha customize as colunas virtuais
        if (method_exists($this, 'getCustomVirtualColumns')) {
            return array_merge($this->getDefaultVirtualColumns(), $this->getCustomVirtualColumns());
        }

        return $this->getDefaultVirtualColumns();
    }

    /**
     * Colunas virtuais padrão
     */
    private function getDefaultVirtualColumns(): array
    {
        return [
            'category' => [
                'full_path' => [
                    'fallback_column' => 'name',
                    'description' => 'Campo virtual que concatena o caminho completo da categoria'
                ],
                'hierarchy' => [
                    'fallback_column' => 'name'
                ]
            ],
            'client' => [
                'display_name' => [
                    'fallback_column' => 'name'
                ]
            ],
            'user' => [
                'full_name' => [
                    'custom_sort' => [
                        'join' => [
                            'table' => 'users',
                            'alias' => 'users_sort',
                            'foreign_key' => 'user_id',
                            'local_key' => 'id'
                        ],
                        'order_by' => 'users_sort.name'
                    ]
                ]
            ]
        ];
    }

    /**
     * Pluraliza um nome (versão corrigida)
     */
    private function pluralize(string $word): string
    {
        // Regras básicas de pluralização
        $irregulars = [
            'category' => 'categories',
            'company' => 'companies',
            'person' => 'people',
            'child' => 'children'
        ];

        if (isset($irregulars[$word])) {
            return $irregulars[$word];
        }

        if (str_ends_with($word, 'y')) {
            return substr($word, 0, -1) . 'ies';
        }

        $endings = ['s', 'x', 'z', 'ch', 'sh'];
        foreach ($endings as $ending) {
            if (str_ends_with($word, $ending)) {
                return $word . 'es';
            }
        }

        return $word . 's';
    }

    /**
     * Método que deve ser implementado na classe que usa o trait
     */
    protected function getTableDefaultSortColumn(): ?string
    {
        return null; // Override na classe filha se necessário
    }
}
