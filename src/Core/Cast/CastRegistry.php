<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Cast;

use Callcocam\LaraGatekeeper\Core\Formatters\DateFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\MoneyFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\NumberFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\CastFormatter;
use Illuminate\Support\Facades\Log;

/**
 * CastRegistry - Gerencia registro de casts customizados e prioridades
 */
class CastRegistry
{
    /**
     * Casts registrados por tipo
     */
    protected static array $casts = [];

    /**
     * Casts registrados por nome de campo
     */
    protected static array $fieldCasts = [];

    /**
     * Prioridades de detecção (maior número = maior prioridade)
     */
    protected static array $priorities = [];

    /**
     * Cache de detecções para performance
     */
    protected static array $cache = [];

    /**
     * Configuração padrão do registry
     */
    protected static array $config = [
        'cache_enabled' => true,
        'max_cache_size' => 1000,
        'auto_clear_cache' => true,
    ];

    /**
     * Registra um cast customizado para um tipo específico
     */
    public static function registerTypeCast(string $type, callable|string $cast, int $priority = 50): void
    {
        if (!isset(static::$casts[$type])) {
            static::$casts[$type] = [];
        }

        static::$casts[$type][] = [
            'cast' => $cast,
            'priority' => $priority,
            'registered_at' => time(),
        ];

        // Ordena por prioridade (maior primeiro)
        usort(static::$casts[$type], fn($a, $b) => $b['priority'] <=> $a['priority']);

        static::clearCache();
    }

    /**
     * Registra um cast para um campo específico
     */
    public static function registerFieldCast(string $fieldName, callable|string $cast, int $priority = 100): void
    {
        static::$fieldCasts[$fieldName] = [
            'cast' => $cast,
            'priority' => $priority,
            'registered_at' => time(),
        ];

        static::clearCache();
    }

    /**
     * Registra múltiplos casts de uma vez
     */
    public static function registerBulkCasts(array $casts): void
    {
        foreach ($casts as $config) {
            if (isset($config['field'])) {
                static::registerFieldCast(
                    $config['field'],
                    $config['cast'],
                    $config['priority'] ?? 100
                );
            } elseif (isset($config['type'])) {
                static::registerTypeCast(
                    $config['type'],
                    $config['cast'],
                    $config['priority'] ?? 50
                );
            }
        }
    }

    /**
     * Registra formatadores padrão do sistema
     */
    public static function registerDefaults(): void
    {
        // Formatadores por tipo de valor
        static::registerTypeCast('boolean', fn($value) => CastFormatter::boolean()->setValue($value), 10);
        static::registerTypeCast('array', fn($value) => CastFormatter::json()->setValue($value), 10);
        static::registerTypeCast('object', fn($value) => CastFormatter::json()->setValue($value), 10);
        static::registerTypeCast('timestamp', fn($value) => DateFormatter::relative()->setValue($value), 20);
        static::registerTypeCast('money', fn($value) => MoneyFormatter::brl()->setValue($value), 20);
        static::registerTypeCast('percentage', fn($value) => NumberFormatter::percentage()->setValue($value), 20);

        // Formatadores por padrão de campo (alta prioridade)
        static::registerFieldCast('created_at', fn($value) => DateFormatter::relative()->setValue($value), 100);
        static::registerFieldCast('updated_at', fn($value) => DateFormatter::relative()->setValue($value), 100);
        static::registerFieldCast('deleted_at', fn($value) => DateFormatter::relative()->setValue($value), 100);
        static::registerFieldCast('published_at', fn($value) => DateFormatter::dateTime()->setValue($value), 100);

        // Campos monetários
        static::registerFieldCast('price', fn($value) => MoneyFormatter::brl()->setValue($value), 90);
        static::registerFieldCast('salary', fn($value) => MoneyFormatter::brl()->setValue($value), 90);
        static::registerFieldCast('cost', fn($value) => MoneyFormatter::brl()->setValue($value), 90);
        static::registerFieldCast('total', fn($value) => MoneyFormatter::brl()->setValue($value), 90);
        static::registerFieldCast('balance', fn($value) => MoneyFormatter::brl()->setValue($value), 90);

        // Campos percentuais
        static::registerFieldCast('rate', fn($value) => NumberFormatter::percentage()->setValue($value), 90);
        static::registerFieldCast('discount', fn($value) => NumberFormatter::percentage()->setValue($value), 90);
        static::registerFieldCast('completion', fn($value) => NumberFormatter::percentage()->setValue($value), 90);

        // Campos booleanos
        static::registerFieldCast('active', fn($value) => CastFormatter::boolean('✅ Ativo', '❌ Inativo')->setValue($value), 90);
        static::registerFieldCast('enabled', fn($value) => CastFormatter::boolean('🟢 Habilitado', '🔴 Desabilitado')->setValue($value), 90);
        static::registerFieldCast('verified', fn($value) => CastFormatter::boolean('✅ Verificado', '⚠️ Não Verificado')->setValue($value), 90);

        // Campos de contagem
        static::registerFieldCast('views', fn($value) => NumberFormatter::abbreviated()->setValue($value), 80);
        static::registerFieldCast('downloads', fn($value) => NumberFormatter::abbreviated()->setValue($value), 80);
        static::registerFieldCast('likes', fn($value) => NumberFormatter::abbreviated()->setValue($value), 80);

        // Campos de tamanho
        static::registerFieldCast('size', fn($value) => NumberFormatter::filesize()->setValue($value), 80);
        static::registerFieldCast('file_size', fn($value) => NumberFormatter::filesize()->setValue($value), 80);

        // Campos JSON/metadata
        static::registerFieldCast('metadata', fn($value) => CastFormatter::json()->setValue($value), 80);
        static::registerFieldCast('settings', fn($value) => CastFormatter::json()->setValue($value), 80);
        static::registerFieldCast('config', fn($value) => CastFormatter::json()->setValue($value), 80);
    }

    /**
     * Resolve o melhor cast para um valor e campo
     */
    public static function resolve(mixed $value, ?string $fieldName = null, array $context = []): ?object
    {
        // Verifica cache primeiro
        $cacheKey = static::getCacheKey($value, $fieldName, $context);
        if (static::$config['cache_enabled'] && isset(static::$cache[$cacheKey])) {
            $cached = static::$cache[$cacheKey];
            if (is_callable($cached)) {
                return $cached($value);
            }
            return $cached;
        }

        $resolved = static::doResolve($value, $fieldName, $context);

        // Armazena no cache
        if (static::$config['cache_enabled'] && $resolved) {
            static::addToCache($cacheKey, $resolved);
        }

        return $resolved;
    }

    /**
     * Executa a resolução do cast
     */
    protected static function doResolve(mixed $value, ?string $fieldName, array $context): ?object
    {

        // Prioridade 1: Cast específico por nome de campo
        if ($fieldName && isset(static::$fieldCasts[$fieldName])) {
            $fieldCast = static::$fieldCasts[$fieldName];
            $result = static::executeCast($fieldCast['cast'], $value, $context);
            if ($result) {
                return $result;
            }
        }

        // Prioridade 2: Casts por padrão de nome de campo (parcial)
        if ($fieldName) {
            foreach (static::$fieldCasts as $pattern => $castConfig) {
                if ($pattern !== $fieldName && str_contains(strtolower($fieldName), strtolower($pattern))) {
                    $result = static::executeCast($castConfig['cast'], $value, $context);
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        // Prioridade 3: Casts por tipo de valor
        $valueType = static::getValueType($value);
        if (isset(static::$casts[$valueType])) {
            foreach (static::$casts[$valueType] as $castConfig) {
                $result = static::executeCast($castConfig['cast'], $value, $context);
                if ($result) {
                    return $result;
                }
            }
        }

        // Prioridade 4: AutoDetector como fallback 
        return AutoDetector::detect($value, $fieldName, $context);
    }

    /**
     * Executa um cast
     */
    protected static function executeCast(callable|string $cast, mixed $value, array $context): ?object
    {
        try {
            if (is_callable($cast)) {
                $result = $cast($value, $context);

                // Se a closure retorna string, envolve em CastFormatter::closure
                if (is_string($result)) {
                    return CastFormatter::closure(fn() => $result)->setValue($value);
                }

                return $result;
            }

            if (is_string($cast) && class_exists($cast)) {
                return new $cast($value);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determina o tipo do valor para busca de casts
     */
    protected static function getValueType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            if ($value instanceof \DateTimeInterface) {
                return 'date';
            }
            return 'object';
        }

        if (is_numeric($value)) {
            // Detecta se parece timestamp
            if (is_int($value) && $value >= 946684800 && $value <= 4102444800) {
                return 'timestamp';
            }

            // Detecta se parece dinheiro
            if (is_float($value) && $value > 0 && $value < 1000000 && (round($value, 2) == $value)) {
                return 'money';
            }

            // Detecta se parece percentual
            if (($value >= 0 && $value <= 1) || ($value >= 0 && $value <= 100 && $value == round($value))) {
                return 'percentage';
            }

            return 'numeric';
        }

        if (is_string($value)) {
            // Verifica padrões específicos
            if (preg_match('/^R\$\s?[\d.,]+$/', $value)) {
                return 'money_string';
            }

            if (preg_match('/^\d+([.,]\d+)?%$/', $value)) {
                return 'percentage_string';
            }

            if (preg_match('/^[\[\{].*[\]\}]$/', $value) && json_decode($value) !== null) {
                return 'json_string';
            }

            try {
                $date = new \DateTime($value);
                return 'date_string';
            } catch (\Exception $e) {
                // Não é data
            }

            return 'string';
        }

        return 'unknown';
    }

    /**
     * Formata automaticamente um valor usando o registry
     */
    public static function autoFormat(mixed $value, ?string $fieldName = null, array $context = []): string
    {
        $formatter = static::resolve($value, $fieldName, $context);

        if (!$formatter) {
            return (string) $value;
        }

        try {
            if (method_exists($formatter, 'format')) {
                return $formatter->format();
            }

            return (string) $formatter;
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * Remove um cast registrado
     */
    public static function unregister(string $key, ?string $type = null): void
    {
        if ($type === 'field' && isset(static::$fieldCasts[$key])) {
            unset(static::$fieldCasts[$key]);
        } elseif ($type === 'type' && isset(static::$casts[$key])) {
            unset(static::$casts[$key]);
        } else {
            // Remove de ambos se não especificado
            unset(static::$fieldCasts[$key], static::$casts[$key]);
        }

        static::clearCache();
    }

    /**
     * Lista todos os casts registrados
     */
    public static function list(): array
    {
        return [
            'field_casts' => static::$fieldCasts,
            'type_casts' => static::$casts,
            'cache_size' => count(static::$cache),
            'config' => static::$config,
        ];
    }

    /**
     * Limpa todos os casts registrados
     */
    public static function clear(): void
    {
        static::$casts = [];
        static::$fieldCasts = [];
        static::$priorities = [];
        static::clearCache();
    }

    /**
     * Configura o registry
     */
    public static function configure(array $config): void
    {
        static::$config = array_merge(static::$config, $config);

        if (!static::$config['cache_enabled']) {
            static::clearCache();
        }
    }

    /**
     * Gera chave de cache
     */
    protected static function getCacheKey(mixed $value, ?string $fieldName, array $context): string
    {
        return md5(serialize([
            'value' => is_object($value) ? get_class($value) : $value,
            'field' => $fieldName,
            'context' => $context,
        ]));
    }

    /**
     * Adiciona ao cache com limite de tamanho
     */
    protected static function addToCache(string $key, mixed $value): void
    {
        if (count(static::$cache) >= static::$config['max_cache_size']) {
            // Remove os primeiros 20% quando atinge o limite
            $toRemove = (int) (static::$config['max_cache_size'] * 0.2);
            static::$cache = array_slice(static::$cache, $toRemove, null, true);
        }

        static::$cache[$key] = $value;
    }

    /**
     * Limpa o cache
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }

    /**
     * Obtém estatísticas do registry
     */
    public static function getStats(): array
    {
        return [
            'field_casts_count' => count(static::$fieldCasts),
            'type_casts_count' => count(static::$casts),
            'cache_size' => count(static::$cache),
            'cache_enabled' => static::$config['cache_enabled'],
            'total_registrations' => count(static::$fieldCasts) + array_sum(array_map('count', static::$casts)),
        ];
    }

    /**
     * Testa performance do registry
     */
    public static function benchmark(array $testData, int $iterations = 100): array
    {
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testData as $item) {
                static::resolve($item['value'], $item['field'] ?? null);
            }
        }

        $end = microtime(true);

        return [
            'iterations' => $iterations,
            'total_time' => $end - $start,
            'average_time' => ($end - $start) / ($iterations * count($testData)),
            'items_per_second' => ($iterations * count($testData)) / ($end - $start),
            'cache_hits' => count(static::$cache),
        ];
    }

    /**
     * Inicialização automática do registry
     */
    public static function initialize(): void
    {
        if (empty(static::$fieldCasts) && empty(static::$casts)) {
            static::registerDefaults();
        }
    }
}
