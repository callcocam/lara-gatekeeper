<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Cast;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema; 

/**
 * ModelIntrospection - Analisa modelos e relacionamentos para sugerir formatadores
 */
class ModelIntrospection
{
    /**
     * Cache de análises de modelos
     */
    protected static array $introspectionCache = [];

    /**
     * Cache de esquemas de tabelas
     */
    protected static array $schemaCache = [];

    /**
     * Tipos de coluna que indicam formatações específicas
     */
    protected static array $columnTypeMapping = [
        // Data/hora
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'date' => 'date',
        'time' => 'time',
        
        // Booleanos
        'boolean' => 'boolean',
        'tinyint' => 'boolean', // Quando length = 1
        
        // Monetário
        'decimal' => 'money',
        'numeric' => 'money',
        'money' => 'money',
        
        // Texto/JSON
        'json' => 'json',
        'text' => 'text',
        'longtext' => 'text',
        'mediumtext' => 'text',
        
        // Números
        'integer' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'smallint' => 'integer',
        'float' => 'float',
        'double' => 'float',
        
        // String
        'varchar' => 'string',
        'char' => 'string',
        'string' => 'string',
    ];

    /**
     * Analisa completamente um modelo
     */
    public static function analyze(string|Model $model): array
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        
        // Verifica cache
        if (isset(static::$introspectionCache[$modelClass])) {
            return static::$introspectionCache[$modelClass];
        }

        $analysis = static::performAnalysis($modelClass);
        
        // Armazena no cache
        static::$introspectionCache[$modelClass] = $analysis;
        
        return $analysis;
    }

    /**
     * Realiza análise completa do modelo
     */
    protected static function performAnalysis(string $modelClass): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        try {
            $model = new $modelClass();
            
            return [
                'model_info' => static::getModelInfo($model),
                'table_schema' => static::getTableSchema($model),
                'relationships' => static::getRelationships($model),
                'suggested_formatters' => static::suggestFormatters($model),
                'fillable_analysis' => static::analyzeFillable($model),
                'cast_suggestions' => static::suggestCasts($model),
                'performance_hints' => static::getPerformanceHints($model),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'model_class' => $modelClass,
            ];
        }
    }

    /**
     * Obtém informações básicas do modelo
     */
    protected static function getModelInfo(Model $model): array
    {
        $reflection = new \ReflectionClass($model);
        
        return [
            'class_name' => get_class($model),
            'table_name' => $model->getTable(),
            'primary_key' => $model->getKeyName(),
            'timestamps' => $model->timestamps,
            'soft_deletes' => method_exists($model, 'trashed'),
            'fillable' => $model->getFillable(),
            'guarded' => $model->getGuarded(),
            'hidden' => $model->getHidden(),
            'visible' => $model->getVisible(),
            'dates' => method_exists($model, 'getDates') ? $model->getDates() : [],
            'casts' => $model->getCasts(),
            'file_path' => $reflection->getFileName(),
        ];
    }

    /**
     * Obtém esquema da tabela
     */
    protected static function getTableSchema(Model $model): array
    {
        $tableName = $model->getTable();
        
        // Verifica cache
        if (isset(static::$schemaCache[$tableName])) {
            return static::$schemaCache[$tableName];
        }

        try {
            $columns = Schema::getColumnListing($tableName);
            $columnDetails = [];

            foreach ($columns as $column) {
                $columnDetails[$column] = static::getColumnDetails($tableName, $column);
            }

            $schema = [
                'table_name' => $tableName,
                'columns' => $columnDetails,
                'indexes' => static::getTableIndexes($tableName),
                'foreign_keys' => static::getForeignKeys($tableName),
            ];

            // Armazena no cache
            static::$schemaCache[$tableName] = $schema;
            
            return $schema;
        } catch (\Exception $e) {
            return [
                'error' => 'Could not retrieve schema: ' . $e->getMessage(),
                'table_name' => $tableName,
            ];
        }
    }

    /**
     * Obtém detalhes de uma coluna usando INFORMATION_SCHEMA
     */
    protected static function getColumnDetails(string $tableName, string $columnName): array
    {
        try {
            // Verifica se a coluna existe
            if (!Schema::hasColumn($tableName, $columnName)) {
                throw new \Exception("Column '{$columnName}' does not exist in table '{$tableName}'");
            }

            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            // Obtém informações detalhadas da coluna via INFORMATION_SCHEMA
            $columnInfo = $connection->selectOne("
                SELECT 
                    COLUMN_NAME,
                    DATA_TYPE,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    EXTRA,
                    COLUMN_COMMENT,
                    COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ", [$database, $tableName, $columnName]);

            if (!$columnInfo) {
                throw new \Exception("Could not retrieve column information");
            }

            return [
                'name' => $columnName,
                'type' => $columnInfo->DATA_TYPE,
                'column_type' => $columnInfo->COLUMN_TYPE,
                'length' => $columnInfo->CHARACTER_MAXIMUM_LENGTH,
                'precision' => $columnInfo->NUMERIC_PRECISION,
                'scale' => $columnInfo->NUMERIC_SCALE,
                'nullable' => $columnInfo->IS_NULLABLE === 'YES',
                'default' => $columnInfo->COLUMN_DEFAULT,
                'auto_increment' => str_contains($columnInfo->EXTRA ?? '', 'auto_increment'),
                'comment' => $columnInfo->COLUMN_COMMENT,
                'suggested_formatter' => static::suggestFormatterForColumn($columnInfo),
            ];
        } catch (\Exception $e) {
            return [
                'name' => $columnName,
                'type' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sugere formatador baseado na coluna
     */
    protected static function suggestFormatterForColumn(object $columnInfo): ?array
    {
        $type = strtolower($columnInfo->DATA_TYPE);
        $name = strtolower($columnInfo->COLUMN_NAME);
        $columnType = strtolower($columnInfo->COLUMN_TYPE ?? '');
        
        // Verifica tipo específico primeiro
        if (isset(static::$columnTypeMapping[$type])) {
            $suggestedType = static::$columnTypeMapping[$type];
            
            // Casos especiais
            if ($type === 'tinyint' && str_contains($columnType, '(1)')) {
                $suggestedType = 'boolean';
            }
            
            if (in_array($type, ['decimal', 'numeric']) && $columnInfo->NUMERIC_SCALE == 2) {
                $suggestedType = 'money';
            }
            
            return [
                'type' => $suggestedType,
                'confidence' => 'high',
                'reason' => 'Column type: ' . $type,
            ];
        }

        // Verifica convenções de nome
        $namePatterns = [
            '/(_at|_date)$/' => ['type' => 'datetime', 'confidence' => 'high'],
            '/(price|cost|amount|salary|total|balance)/' => ['type' => 'money', 'confidence' => 'medium'],
            '/(is_|has_|can_)/' => ['type' => 'boolean', 'confidence' => 'medium'],
            '/(email)/' => ['type' => 'email', 'confidence' => 'medium'],
            '/(phone|mobile)/' => ['type' => 'phone', 'confidence' => 'medium'],
            '/(url|link|website)/' => ['type' => 'url', 'confidence' => 'medium'],
            '/(json|data|meta)/' => ['type' => 'json', 'confidence' => 'low'],
        ];

        foreach ($namePatterns as $pattern => $suggestion) {
            if (preg_match($pattern, $name)) {
                $suggestion['reason'] = 'Name pattern: ' . $pattern;
                return $suggestion;
            }
        }

        return null;
    }

    /**
     * Obtém índices da tabela usando INFORMATION_SCHEMA
     */
    protected static function getTableIndexes(string $tableName): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            $indexes = $connection->select("
                SELECT 
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    SEQ_IN_INDEX
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ?
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ", [$database, $tableName]);

            $indexInfo = [];
            $groupedIndexes = collect($indexes)->groupBy('INDEX_NAME');

            foreach ($groupedIndexes as $indexName => $columns) {
                $first = $columns->first();
                $indexInfo[] = [
                    'name' => $indexName,
                    'columns' => $columns->pluck('COLUMN_NAME')->toArray(),
                    'unique' => $first->NON_UNIQUE == 0,
                    'primary' => $indexName === 'PRIMARY',
                ];
            }

            return $indexInfo;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém chaves estrangeiras usando INFORMATION_SCHEMA
     */
    protected static function getForeignKeys(string $tableName): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            $foreignKeys = $connection->select("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $tableName]);

            $fkInfo = [];
            foreach ($foreignKeys as $fk) {
                $fkInfo[] = [
                    'name' => $fk->CONSTRAINT_NAME,
                    'local_columns' => [$fk->COLUMN_NAME],
                    'foreign_table' => $fk->REFERENCED_TABLE_NAME,
                    'foreign_columns' => [$fk->REFERENCED_COLUMN_NAME],
                ];
            }

            return $fkInfo;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Detecta relacionamentos do modelo
     */
    protected static function getRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new \ReflectionClass($model);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getNumberOfParameters() === 0 && 
                $method->getDeclaringClass()->getName() === get_class($model) &&
                !in_array($method->getName(), ['getKey', 'getTable', 'getKeyName', 'getKeyType'])) {
                
                try {
                    $result = $method->invoke($model);
                    
                    if ($result instanceof Relation) {
                        $relationships[] = [
                            'name' => $method->getName(),
                            'type' => class_basename(get_class($result)),
                            'related_model' => get_class($result->getRelated()),
                            'foreign_key' => static::getRelationshipKey($result, 'foreign'),
                            'local_key' => static::getRelationshipKey($result, 'local'),
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignora métodos que não são relacionamentos
                }
            }
        }

        return $relationships;
    }

    /**
     * Obtém chave do relacionamento
     */
    protected static function getRelationshipKey(Relation $relation, string $type): ?string
    {
        try {
            return match($type) {
                'foreign' => method_exists($relation, 'getForeignKeyName') ? $relation->getForeignKeyName() : 
                           (method_exists($relation, 'getForeignKey') ? $relation->getForeignKey() : null),
                'local' => method_exists($relation, 'getLocalKeyName') ? $relation->getLocalKeyName() : 
                          (method_exists($relation, 'getLocalKey') ? $relation->getLocalKey() : null),
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sugere formatadores para o modelo
     */
    protected static function suggestFormatters(Model $model): array
    {
        $suggestions = [];
        $schema = static::getTableSchema($model);
        
        if (isset($schema['columns'])) {
            foreach ($schema['columns'] as $column) {
                if (isset($column['suggested_formatter']) && $column['suggested_formatter']) {
                    $suggestions[$column['name']] = $column['suggested_formatter'];
                }
            }
        }

        // Adiciona sugestões baseadas em casts existentes
        $casts = $model->getCasts();
        foreach ($casts as $field => $castType) {
            if (!isset($suggestions[$field])) {
                $suggestions[$field] = [
                    'type' => $castType,
                    'confidence' => 'high',
                    'reason' => 'Existing cast: ' . $castType,
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Analisa campos fillable
     */
    protected static function analyzeFillable(Model $model): array
    {
        $fillable = $model->getFillable();
        $analysis = [];
        $schema = static::getTableSchema($model);

        foreach ($fillable as $field) {
            $columnInfo = $schema['columns'][$field] ?? null;
            
            $analysis[$field] = [
                'fillable' => true,
                'suggested_input_type' => static::suggestInputType($field, $columnInfo),
                'validation_suggestions' => static::suggestValidation($field, $columnInfo),
            ];
        }

        return $analysis;
    }

    /**
     * Sugere tipo de input para campo
     */
    protected static function suggestInputType(string $field, ?array $columnInfo = null): string
    {
        // Verifica tipo da coluna primeiro
        if ($columnInfo) {
            $type = strtolower($columnInfo['type']);
            
            if (in_array($type, ['datetime', 'timestamp'])) {
                return 'datetime-local';
            }
            if ($type === 'date') {
                return 'date';
            }
            if ($type === 'time') {
                return 'time';
            }
            if (in_array($type, ['decimal', 'float', 'double', 'numeric'])) {
                return 'number';
            }
            if ($type === 'boolean' || ($type === 'tinyint' && str_contains($columnInfo['column_type'] ?? '', '(1)'))) {
                return 'checkbox';
            }
            if (in_array($type, ['text', 'longtext', 'mediumtext'])) {
                return 'textarea';
            }
        }

        // Fallback para padrões de nome
        $patterns = [
            'email' => 'email',
            'password' => 'password',
            'phone|mobile' => 'tel',
            'url|website|link' => 'url',
            'date|birth' => 'date',
            'time' => 'time',
            'datetime' => 'datetime-local',
            'price|cost|amount|salary' => 'number',
            'description|content|body|text' => 'textarea',
            'is_|has_|can_' => 'checkbox',
        ];

        foreach ($patterns as $pattern => $inputType) {
            if (preg_match("/($pattern)/i", $field)) {
                return $inputType;
            }
        }

        return 'text';
    }

    /**
     * Sugere validações para campo
     */
    protected static function suggestValidation(string $field, ?array $columnInfo = null): array
    {
        $validations = [];
        
        // Adiciona required se não for nullable
        if ($columnInfo && !$columnInfo['nullable']) {
            $validations[] = 'required';
        }

        // Validações baseadas no tipo da coluna
        if ($columnInfo) {
            $type = strtolower($columnInfo['type']);
            
            if (in_array($type, ['varchar', 'char', 'string']) && $columnInfo['length']) {
                $validations[] = 'string';
                $validations[] = 'max:' . $columnInfo['length'];
            }
            
            if (in_array($type, ['decimal', 'float', 'double', 'numeric'])) {
                $validations[] = 'numeric';
            }
            
            if (in_array($type, ['integer', 'int', 'bigint', 'smallint'])) {
                $validations[] = 'integer';
            }
            
            if ($type === 'boolean' || ($type === 'tinyint' && str_contains($columnInfo['column_type'] ?? '', '(1)'))) {
                $validations[] = 'boolean';
            }
            
            if (in_array($type, ['datetime', 'timestamp', 'date'])) {
                $validations[] = 'date';
            }
        }

        // Validações baseadas em padrões de nome
        $patterns = [
            'email' => ['email'],
            'password' => ['min:8'],
            'phone|mobile' => ['regex:/^[\+]?[\d\s\(\)\-]+$/'],
            'url|website|link' => ['url'],
            'price|cost|amount|salary' => ['min:0'],
        ];

        foreach ($patterns as $pattern => $rules) {
            if (preg_match("/($pattern)/i", $field)) {
                $validations = array_merge($validations, $rules);
                break;
            }
        }

        return array_unique($validations);
    }

    /**
     * Sugere casts para o modelo
     */
    protected static function suggestCasts(Model $model): array
    {
        $suggestions = [];
        $schema = static::getTableSchema($model);
        $existingCasts = $model->getCasts();

        if (isset($schema['columns'])) {
            foreach ($schema['columns'] as $column) {
                $field = $column['name'];
                
                // Pula se já tem cast definido
                if (isset($existingCasts[$field])) {
                    continue;
                }
                
                $suggestion = static::suggestCastForColumn($column);
                if ($suggestion) {
                    $suggestions[$field] = $suggestion;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Sugere cast para uma coluna
     */
    protected static function suggestCastForColumn(array $column): ?string
    {
        $type = strtolower($column['type']);
        $name = strtolower($column['name']);
        $columnType = strtolower($column['column_type'] ?? '');

        // Mapeamento direto de tipos
        $typeCasts = [
            'boolean' => 'boolean',
            'json' => 'array',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            'date' => 'date',
            'decimal' => $column['scale'] ? 'decimal:' . $column['scale'] : 'decimal:2',
            'float' => 'float',
            'double' => 'float',
        ];

        if (isset($typeCasts[$type])) {
            return $typeCasts[$type];
        }

        // Verifica tinyint(1) como boolean
        if ($type === 'tinyint' && str_contains($columnType, '(1)')) {
            return 'boolean';
        }

        // Sugestões baseadas em nome
        $namePatterns = [
            '/_at$|_date$/' => 'datetime',
            '/^is_|^has_|^can_/' => 'boolean',
            '/settings|config|meta|data/' => 'array',
        ];

        foreach ($namePatterns as $pattern => $cast) {
            if (preg_match($pattern, $name)) {
                return $cast;
            }
        }

        return null;
    }

    /**
     * Obtém dicas de performance
     */
    protected static function getPerformanceHints(Model $model): array
    {
        $hints = [];
        $relationships = static::getRelationships($model);
        
        // Verifica N+1 queries potenciais
        if (count($relationships) > 3) {
            $hints[] = [
                'type' => 'n_plus_one',
                'message' => 'Modelo com muitos relacionamentos. Considere usar eager loading.',
                'suggestion' => 'Use with() para carregar relacionamentos necessários.',
            ];
        }

        // Verifica timestamps
        if ($model->timestamps) {
            $hints[] = [
                'type' => 'timestamps',
                'message' => 'Timestamps habilitados. Campos created_at/updated_at serão formatados automaticamente.',
                'suggestion' => 'Use DateFormatter::relative() para melhor UX.',
            ];
        }

        // Verifica campos fillable
        $fillableCount = count($model->getFillable());
        if ($fillableCount > 10) {
            $hints[] = [
                'type' => 'fillable',
                'message' => 'Muitos campos fillable (' . $fillableCount . '). Considere agrupar em seções.',
                'suggestion' => 'Use tabs ou steps no formulário para melhor UX.',
            ];
        }

        // Verifica índices em relacionamentos
        $schema = static::getTableSchema($model);
        $indexes = collect($schema['indexes'] ?? [])->pluck('columns')->flatten();
        
        foreach ($relationships as $relationship) {
            if ($relationship['foreign_key'] && !$indexes->contains($relationship['foreign_key'])) {
                $hints[] = [
                    'type' => 'missing_index',
                    'message' => 'Chave estrangeira sem índice: ' . $relationship['foreign_key'],
                    'suggestion' => 'Adicione índice para melhor performance em consultas.',
                ];
            }
        }

        return $hints;
    }

    /**
     * Gera relatório completo para um modelo
     */
    public static function generateReport(string|Model $model): array
    {
        $analysis = static::analyze($model);
        
        return [
            'generated_at' => now()->toISOString(),
            'model_class' => is_string($model) ? $model : get_class($model),
            'analysis' => $analysis,
            'summary' => static::generateSummary($analysis),
            'recommendations' => static::generateRecommendations($analysis),
        ];
    }

    /**
     * Gera resumo da análise
     */
    protected static function generateSummary(array $analysis): array
    {
        return [
            'total_columns' => count($analysis['table_schema']['columns'] ?? []),
            'total_relationships' => count($analysis['relationships'] ?? []),
            'suggested_formatters' => count($analysis['suggested_formatters'] ?? []),
            'cast_suggestions' => count($analysis['cast_suggestions'] ?? []),
            'performance_hints' => count($analysis['performance_hints'] ?? []),
            'fillable_fields' => count($analysis['fillable_analysis'] ?? []),
        ];
    }

    /**
     * Gera recomendações
     */
    protected static function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        // Recomendações de casts
        if (!empty($analysis['cast_suggestions'])) {
            $recommendations[] = [
                'category' => 'casts',
                'message' => 'Adicione casts para melhor tipagem de dados',
                'code_example' => static::generateCastExample($analysis['cast_suggestions']),
            ];
        }

        // Recomendações de formatadores
        if (!empty($analysis['suggested_formatters'])) {
            $recommendations[] = [
                'category' => 'formatters',
                'message' => 'Use formatadores automáticos para melhor apresentação',
                'code_example' => 'EloquentCastIntegration::detectAndRegister(' . ($analysis['model_info']['class_name'] ?? 'YourModel') . '::class);',
            ];
        }

        // Recomendações de performance
        $performanceHints = $analysis['performance_hints'] ?? [];
        $missingIndexes = array_filter($performanceHints, fn($hint) => $hint['type'] === 'missing_index');
        
        if (!empty($missingIndexes)) {
            $recommendations[] = [
                'category' => 'performance',
                'message' => 'Adicione índices em chaves estrangeiras para melhor performance',
                'code_example' => static::generateIndexExample($missingIndexes),
            ];
        }

        return $recommendations;
    }

    /**
     * Gera exemplo de código para casts
     */
    protected static function generateCastExample(array $castSuggestions): string
    {
        $code = "protected \$casts = [\n";
        
        foreach ($castSuggestions as $field => $cast) {
            $code .= "    '$field' => '$cast',\n";
        }
        
        $code .= "];";
        
        return $code;
    }

    /**
     * Gera exemplo de migração para índices
     */
    protected static function generateIndexExample(array $missingIndexes): string
    {
        $code = "// Adicione em uma migração:\n";
        
        foreach ($missingIndexes as $hint) {
            if (preg_match('/Chave estrangeira sem índice: (.+)/', $hint['message'], $matches)) {
                $field = $matches[1];
                $code .= "\$table->index('$field');\n";
            }
        }
        
        return $code;
    }

    /**
     * Verifica se uma tabela existe
     */
    public static function tableExists(string $tableName): bool
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtém todas as tabelas do banco
     */
    public static function getAllTables(): array
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();
            
            $tables = $connection->select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_TYPE = 'BASE TABLE'
            ", [$database]);
            
            return array_column($tables, 'TABLE_NAME');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Limpa cache
     */
    public static function clearCache(): void
    {
        static::$introspectionCache = [];
        static::$schemaCache = [];
    }

    /**
     * Obtém estatísticas globais
     */
    public static function getGlobalStats(): array
    {
        return [
            'models_analyzed' => count(static::$introspectionCache),
            'tables_cached' => count(static::$schemaCache),
            'cache_memory_usage' => memory_get_usage(),
            'available_tables' => count(static::getAllTables()),
        ];
    }

    /**
     * Valida se um modelo pode ser analisado
     */
    public static function canAnalyze(string $modelClass): bool
    {
        if (!class_exists($modelClass)) {
            return false;
        }
        
        if (!is_subclass_of($modelClass, Model::class)) {
            return false;
        }
        
        try {
            $model = new $modelClass();
            return static::tableExists($model->getTable());
        } catch (\Exception $e) {
            return false;
        }
    }
}