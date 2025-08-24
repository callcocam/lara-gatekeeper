<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Cast;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Callcocam\LaraGatekeeper\Core\Formatters\DateFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\MoneyFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\NumberFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\CastFormatter;

/**
 * EloquentCastIntegration - Integra automaticamente com casts do Laravel
 */
class EloquentCastIntegration
{
    /**
     * Cache de casts detectados por modelo
     */
    protected static array $modelCastsCache = [];

    /**
     * Cache de metadados de modelos
     */
    protected static array $modelMetadataCache = [];

    /**
     * Mapeamento de casts Eloquent para nossos formatadores
     */
    protected static array $castMapping = [
        // Casts de data/hora
        'datetime' => DateFormatter::class,
        'date' => DateFormatter::class,
        'timestamp' => DateFormatter::class,
        'immutable_datetime' => DateFormatter::class,
        'immutable_date' => DateFormatter::class,
        
        // Casts booleanos
        'boolean' => CastFormatter::class,
        'bool' => CastFormatter::class,
        
        // Casts JSON/array
        'array' => CastFormatter::class,
        'json' => CastFormatter::class,
        'object' => CastFormatter::class,
        'collection' => CastFormatter::class,
        
        // Casts numéricos
        'integer' => NumberFormatter::class,
        'int' => NumberFormatter::class,
        'float' => NumberFormatter::class,
        'double' => NumberFormatter::class,
        'real' => NumberFormatter::class,
        'decimal' => MoneyFormatter::class,
        
        // Casts de string
        'string' => null, // Sem formatação especial
        'encrypted' => null,
        'hashed' => null,
    ];

    /**
     * Configurações especiais por tipo de cast
     */
    protected static array $castConfigurations = [
        'datetime' => ['mode' => 'relative'],
        'date' => ['mode' => 'date'],
        'timestamp' => ['mode' => 'relative'],
        'boolean' => ['true_label' => '✅ Sim', 'false_label' => '❌ Não'],
        'array' => ['cast_type' => 'json'],
        'json' => ['cast_type' => 'json'],
        'object' => ['cast_type' => 'json'],
        'decimal' => ['currency' => 'BRL'],
        'float' => ['format' => 'decimal', 'decimals' => 2],
        'integer' => ['format' => 'decimal', 'decimals' => 0],
    ];

    /**
     * Detecta e registra casts de um modelo no CastRegistry
     */
    public static function detectAndRegister(string|Model $model): array
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        
        // Verifica cache primeiro
        if (isset(static::$modelCastsCache[$modelClass])) {
            return static::$modelCastsCache[$modelClass];
        }

        $detectedCasts = static::detectModelCasts($modelClass);
        
        // Registra no CastRegistry
        foreach ($detectedCasts as $field => $config) {
            if ($config['formatter_class']) {
                CastRegistry::registerFieldCast(
                    $field,
                    static::createFormatterClosure($config),
                    $config['priority']
                );
            }
        }

        // Armazena no cache
        static::$modelCastsCache[$modelClass] = $detectedCasts;

        return $detectedCasts;
    }

    /**
     * Detecta casts de um modelo
     */
    protected static function detectModelCasts(string $modelClass): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        try {
            $model = new $modelClass();
            $detectedCasts = [];

            // 1. Casts explícitos definidos no modelo
            $explicitCasts = static::getExplicitCasts($model);
            foreach ($explicitCasts as $field => $castType) {
                $detectedCasts[$field] = static::mapCastToFormatter($field, $castType, 'explicit', 95);
            }

            // 2. Campos de data automáticos (dates array)
            $dateCasts = static::getDateCasts($model);
            foreach ($dateCasts as $field) {
                if (!isset($detectedCasts[$field])) {
                    $detectedCasts[$field] = static::mapCastToFormatter($field, 'datetime', 'dates', 90);
                }
            }

            // 3. Atributos com Attribute (Laravel 9+)
            $attributeCasts = static::getAttributeCasts($model);
            foreach ($attributeCasts as $field => $config) {
                if (!isset($detectedCasts[$field])) {
                    $detectedCasts[$field] = $config;
                }
            }

            // 4. Casts baseados em convenções de nome
            $conventionCasts = static::getConventionCasts($model);
            foreach ($conventionCasts as $field => $config) {
                if (!isset($detectedCasts[$field])) {
                    $detectedCasts[$field] = $config;
                }
            }

            return $detectedCasts;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém casts explícitos do modelo
     */
    protected static function getExplicitCasts(Model $model): array
    {
        try {
            return $model->getCasts();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém campos de data do modelo
     */
    protected static function getDateCasts(Model $model): array
    {
        try {
            $dates = $model->getDates();
            
            // Adiciona campos padrão se não estiverem listados
            $defaultDates = ['created_at', 'updated_at', 'deleted_at'];
            foreach ($defaultDates as $field) {
                if (!in_array($field, $dates) && $model->hasAttribute($field)) {
                    $dates[] = $field;
                }
            }

            return $dates;
        } catch (\Exception $e) {
            return ['created_at', 'updated_at', 'deleted_at'];
        }
    }

    /**
     * Obtém casts baseados em Attributes (Laravel 9+)
     */
    protected static function getAttributeCasts(Model $model): array
    {
        $attributeCasts = [];

        try {
            $reflection = new \ReflectionClass($model);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Verifica se o método retorna um Attribute
                $returnType = $method->getReturnType();
                if ($returnType && $returnType->getName() === Attribute::class) {
                    $fieldName = $method->getName();
                    
                    // Remove prefixos comuns de acessors/mutators
                    $fieldName = preg_replace('/^(get|set)([A-Z])/', '$2', $fieldName);
                    $fieldName = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($fieldName)));

                    $attributeCasts[$fieldName] = static::mapCastToFormatter($fieldName, 'attribute', 'attribute', 85);
                }
            }
        } catch (\Exception $e) {
            // Ignora erros de reflexão
        }

        return $attributeCasts;
    }

    /**
     * Obtém casts baseados em convenções de nome
     */
    protected static function getConventionCasts(Model $model): array
    {
        $conventionCasts = [];

        try {
            $fillable = $model->getFillable();
            
            foreach ($fillable as $field) {
                $field = strtolower($field);
                $config = static::detectFieldConvention($field);
                
                if ($config) {
                    $conventionCasts[$field] = $config;
                }
            }
        } catch (\Exception $e) {
            // Ignora erros
        }

        return $conventionCasts;
    }

    /**
     * Detecta tipo baseado em convenções de nome
     */
    protected static function detectFieldConvention(string $field): ?array
    {
        $patterns = [
            // Campos monetários
            'price|cost|value|amount|salary|total|subtotal|balance|revenue' => ['money', 80],
            
            // Campos percentuais
            'rate|percentage|completion|progress|discount|tax' => ['percentage', 80],
            
            // Campos booleanos
            'is_|has_|can_|active|enabled|published|verified|confirmed' => ['boolean', 80],
            
            // Campos de contagem
            'views|likes|comments|shares|downloads|count' => ['count', 75],
            
            // Campos de tamanho
            'size|file_size|attachment_size|image_size' => ['filesize', 75],
            
            // Campos JSON
            'metadata|settings|config|options|data|attributes' => ['json', 75],
            
            // Campos de data (além dos padrão)
            '_at$|_date$|birth_date|start_date|end_date|published_at' => ['datetime', 75],
        ];

        foreach ($patterns as $pattern => $config) {
            if (preg_match("/($pattern)/i", $field)) {
                [$type, $priority] = $config;
                return static::mapCastToFormatter($field, $type, 'convention', $priority);
            }
        }

        return null;
    }

    /**
     * Mapeia um cast para um formatador
     */
    protected static function mapCastToFormatter(string $field, string $castType, string $source, int $priority): array
    {
        // Remove parâmetros do cast (ex: "decimal:2" -> "decimal")
        $baseCastType = explode(':', $castType)[0];
        
        $formatterClass = static::$castMapping[$baseCastType] ?? null;
        $config = static::$castConfigurations[$baseCastType] ?? [];

        return [
            'field' => $field,
            'cast_type' => $castType,
            'base_cast_type' => $baseCastType,
            'formatter_class' => $formatterClass,
            'config' => $config,
            'source' => $source,
            'priority' => $priority,
        ];
    }

    /**
     * Cria closure para formatador baseado na configuração
     */
    protected static function createFormatterClosure(array $config): \Closure
    {
        return function($value) use ($config) {
            $formatterClass = $config['formatter_class'];
            $formatterConfig = $config['config'];
            
            if (!$formatterClass) {
                return (string) $value;
            }

            try {
                switch ($formatterClass) {
                    case DateFormatter::class:
                        $mode = $formatterConfig['mode'] ?? 'relative';
                        return match($mode) {
                            'relative' => DateFormatter::relative()->setValue($value),
                            'date' => DateFormatter::date()->setValue($value),
                            'datetime' => DateFormatter::dateTime()->setValue($value),
                            default => DateFormatter::relative()->setValue($value),
                        };

                    case MoneyFormatter::class:
                        $currency = $formatterConfig['currency'] ?? 'BRL';
                        return match($currency) {
                            'BRL' => MoneyFormatter::brl()->setValue($value),
                            'USD' => MoneyFormatter::usd()->setValue($value),
                            'EUR' => MoneyFormatter::eur()->setValue($value),
                            default => MoneyFormatter::brl()->setValue($value),
                        };

                    case NumberFormatter::class:
                        $format = $formatterConfig['format'] ?? 'decimal';
                        $decimals = $formatterConfig['decimals'] ?? 2;
                        
                        return match($format) {
                            'percentage' => NumberFormatter::percentage()->setValue($value),
                            'filesize' => NumberFormatter::filesize()->setValue($value),
                            'abbreviated' => NumberFormatter::abbreviated()->setValue($value),
                            'ordinal' => NumberFormatter::ordinal()->setValue($value),
                            default => NumberFormatter::decimal($decimals)->setValue($value),
                        };

                    case CastFormatter::class:
                        $castType = $formatterConfig['cast_type'] ?? 'auto';
                        
                        return match($castType) {
                            'json' => CastFormatter::json()->setValue($value),
                            'boolean' => CastFormatter::boolean(
                                $formatterConfig['true_label'] ?? '✅ Sim',
                                $formatterConfig['false_label'] ?? '❌ Não'
                            )->setValue($value),
                            default => CastFormatter::auto()->setValue($value),
                        };

                    default:
                        return CastFormatter::auto()->setValue($value);
                }
            } catch (\Exception $e) {
                return (string) $value;
            }
        };
    }

    /**
     * Registra casts de múltiplos modelos
     */
    public static function registerMultipleModels(array $models): array
    {
        $allDetectedCasts = [];

        foreach ($models as $model) {
            $casts = static::detectAndRegister($model);
            $modelClass = is_string($model) ? $model : get_class($model);
            $allDetectedCasts[$modelClass] = $casts;
        }

        return $allDetectedCasts;
    }

    /**
     * Obtém casts detectados para um modelo (sem registrar)
     */
    public static function getCastsForModel(string|Model $model): array
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        
        if (isset(static::$modelCastsCache[$modelClass])) {
            return static::$modelCastsCache[$modelClass];
        }

        return static::detectModelCasts($modelClass);
    }

    /**
     * Limpa cache de casts
     */
    public static function clearCache(): void
    {
        static::$modelCastsCache = [];
        static::$modelMetadataCache = [];
    }

    /**
     * Obtém estatísticas dos casts detectados
     */
    public static function getStats(): array
    {
        $totalModels = count(static::$modelCastsCache);
        $totalCasts = 0;
        $castsBySource = [];
        $castsByType = [];

        foreach (static::$modelCastsCache as $modelCasts) {
            foreach ($modelCasts as $cast) {
                $totalCasts++;
                $source = $cast['source'];
                $type = $cast['base_cast_type'];
                
                $castsBySource[$source] = ($castsBySource[$source] ?? 0) + 1;
                $castsByType[$type] = ($castsByType[$type] ?? 0) + 1;
            }
        }

        return [
            'total_models' => $totalModels,
            'total_casts' => $totalCasts,
            'casts_by_source' => $castsBySource,
            'casts_by_type' => $castsByType,
            'cache_size' => count(static::$modelCastsCache),
        ];
    }

    /**
     * Configura mapeamentos customizados
     */
    public static function configureMappings(array $customMappings): void
    {
        static::$castMapping = array_merge(static::$castMapping, $customMappings);
        static::clearCache();
    }

    /**
     * Inicialização automática para modelos comuns
     */
    public static function autoDiscoverModels(string $namespace = 'App\\Models'): array
    {
        $discoveredModels = [];

        try {
            $path = app_path('Models');
            
            if (!is_dir($path)) {
                return $discoveredModels;
            }

            $files = glob($path . '/*.php');
            
            foreach ($files as $file) {
                $className = $namespace . '\\' . basename($file, '.php');
                
                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    $casts = static::detectAndRegister($className);
                    if (!empty($casts)) {
                        $discoveredModels[$className] = $casts;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignora erros de descoberta
        }

        return $discoveredModels;
    }
} 