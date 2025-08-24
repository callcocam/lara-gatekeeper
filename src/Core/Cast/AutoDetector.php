<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Cast;

use Carbon\Carbon;
use Callcocam\LaraGatekeeper\Core\Formatters\DateFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\MoneyFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\NumberFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\CastFormatter;
use Illuminate\Support\Facades\Log;

/**
 * AutoDetector - Detecta automaticamente o tipo de dados e mapeia para formatadores
 */
class AutoDetector
{
    /**
     * Padrões para detecção de tipos
     */
    protected static array $patterns = [
        'email' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        'url' => '/^https?:\/\/[^\s]+$/',
        'phone' => '/^[\+]?[\d\s\(\)\-]{10,}$/',
        'cpf' => '/^\d{3}\.\d{3}\.\d{3}\-\d{2}$/',
        'cnpj' => '/^\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}$/',
        'cep' => '/^\d{5}\-?\d{3}$/',
        'money_brl' => '/^R\$\s?[\d.,]+$/',
        'money_usd' => '/^\$[\d.,]+$/',
        'money_eur' => '/^€[\d.,]+$/',
        'percentage' => '/^\d+([.,]\d+)?%$/',
        'filesize' => '/^\d+([.,]\d+)?\s?(B|KB|MB|GB|TB)$/i',
        'json' => '/^[\[\{].*[\]\}]$/',
        'uuid' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
    ];

    /**
     * Campos comuns que indicam tipos específicos
     */
    protected static array $fieldHints = [
        // Campos de data/hora
        'date' => ['created_at', 'updated_at', 'deleted_at', 'birth_date', 'start_date', 'end_date', 'published_at'],

        // Campos monetários
        'money' => ['price', 'cost', 'value', 'amount', 'salary', 'total', 'subtotal', 'balance', 'revenue'],

        // Campos percentuais
        'percentage' => ['rate', 'percentage', 'completion', 'progress', 'discount', 'tax'],

        // Campos de tamanho
        'filesize' => ['size', 'file_size', 'attachment_size', 'image_size'],

        // Campos booleanos
        'boolean' => ['active', 'enabled', 'published', 'verified', 'confirmed', 'is_active', 'is_admin'],

        // Campos de contagem
        'count' => ['views', 'likes', 'comments', 'shares', 'downloads', 'count', 'total'],

        // Campos de posição/ordem
        'ordinal' => ['position', 'order', 'rank', 'level', 'sort'],

        // Campos JSON
        'json' => ['metadata', 'settings', 'config', 'options', 'data', 'attributes'],
    ];

    /**
     * Detecta automaticamente o tipo de um valor e retorna o formatador apropriado
     */
    public static function detect(mixed $value, ?string $fieldName = null, array $context = []): ?object
    {
        // Se valor é nulo ou vazio, retorna null
        if (self::isEmpty($value)) {
            return null;
        }

        // Verifica dicas do nome do campo primeiro
        if ($fieldName) {
            $formatterByField = self::detectByFieldName($fieldName, $value);
            if ($formatterByField) {
                return $formatterByField;
            }
        } 

        // Detecção por tipo do valor
        return self::detectByValue($value, $context);
    }

    /**
     * Detecta tipo baseado no nome do campo
     */
    protected static function detectByFieldName(string $fieldName, mixed $value): ?object
    {
        $fieldName = strtolower($fieldName);

        // Campos de data
        if (self::fieldMatchesCategory('date', $fieldName)) {
            if (self::isDateValue($value)) {
                return DateFormatter::relative();
            }
        }

        // Campos monetários
        if (self::fieldMatchesCategory('money', $fieldName)) {
            if (is_numeric($value)) {
                return MoneyFormatter::brl();
            }
        }

        // Campos percentuais
        if (self::fieldMatchesCategory('percentage', $fieldName)) {
            if (is_numeric($value)) {
                return NumberFormatter::percentage();
            }
        }

        // Campos de tamanho de arquivo
        if (self::fieldMatchesCategory('filesize', $fieldName)) {
            if (is_numeric($value)) {
                return NumberFormatter::filesize();
            }
        }

        // Campos booleanos
        if (self::fieldMatchesCategory('boolean', $fieldName)) {
            return CastFormatter::boolean('✅ Sim', '❌ Não');
        }

        // Campos de contagem
        if (self::fieldMatchesCategory('count', $fieldName)) {
            if (is_numeric($value) && $value >= 1000) {
                return NumberFormatter::abbreviated();
            }
        }

        // Campos ordinais
        if (self::fieldMatchesCategory('ordinal', $fieldName)) {
            if (is_numeric($value)) {
                return NumberFormatter::ordinal();
            }
        }

        // Campos JSON
        if (self::fieldMatchesCategory('json', $fieldName)) {
            if (is_array($value) || self::isJsonString($value)) {
                return CastFormatter::json();
            }
        }

        return null;
    }

    /**
     * Detecta tipo baseado no valor
     */
    protected static function detectByValue(mixed $value, array $context = []): ?object
    {
        // Boolean
        if (is_bool($value)) {
            return CastFormatter::boolean();
        }

        // Array ou objeto
        if (is_array($value) || is_object($value)) {
            return CastFormatter::json();
        }

        // String patterns
        if (is_string($value)) { 
            return self::detectStringPattern($value);
        }

        // Números
        if (is_numeric($value)) { 
            return self::detectNumericPattern($value, $context);
        }

        // Data/hora como objeto
        if ($value instanceof \DateTimeInterface) {
            return DateFormatter::relative();
        }

        // Carbon
        if ($value instanceof Carbon) {
            return DateFormatter::relative();
        }

        // Padrão: cast automático
        return CastFormatter::auto();
    }

    /**
     * Detecta padrões em strings
     */
    protected static function detectStringPattern(string $value): ?object
    {
        // Verifica padrões conhecidos
        foreach (self::$patterns as $type => $pattern) {
            if (preg_match($pattern, $value)) {
                return match ($type) {
                    'money_brl' => MoneyFormatter::brl(),
                    'money_usd' => MoneyFormatter::usd(),
                    'money_eur' => MoneyFormatter::eur(),
                    'percentage' => NumberFormatter::percentage(),
                    'filesize' => NumberFormatter::filesize(),
                    'json' => CastFormatter::json(),
                    'phone' => CastFormatter::phone($value),
                    default => CastFormatter::auto($value),
                };
            }
        }

        // Tenta detectar como data
        if (self::isDateString($value)) {
            return DateFormatter::relative();
        }

        // String comum
        return null;
    }

    /**
     * Detecta padrões em números
     */
    protected static function detectNumericPattern(float|int $value, array $context = []): ?object
    {
        $value = (float) $value;

        // Timestamp (número grande)
        if (self::looksLikeTimestamp($value)) {
            return DateFormatter::relative();
        }

        // Percentual (0-1 ou 0-100)
        if (self::looksLikePercentage($value)) {
            return NumberFormatter::percentage();
        }

        // Dinheiro (padrão com 2 decimais)
        if (self::looksLikeMoney($value)) {
            return MoneyFormatter::brl();
        }

        // Tamanho de arquivo (bytes)
        if (self::looksLikeFilesize($value)) {
            return NumberFormatter::filesize();
        }

        // Número grande (abreviar)
        if ($value >= 10000) {
            return NumberFormatter::abbreviated();
        }

        // Número decimal comum
        if (is_float($value) || $value != intval($value)) {
            return NumberFormatter::decimal(2);
        }

        // Número inteiro comum
        return NumberFormatter::decimal(0);
    }

    /**
     * Verifica se um campo pertence a uma categoria
     */
    protected static function fieldMatchesCategory(string $category, string $fieldName): bool
    {
        $fields = self::$fieldHints[$category] ?? [];

        foreach ($fields as $pattern) {
            if (str_contains($fieldName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se valor está vazio
     */
    protected static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Verifica se é um valor de data
     */
    protected static function isDateValue(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface || $value instanceof Carbon) {
            return true;
        }

        if (is_string($value)) {
            return self::isDateString($value);
        }

        if (is_numeric($value)) {
            return self::looksLikeTimestamp($value);
        }

        return false;
    }

    /**
     * Verifica se string é data válida
     */
    protected static function isDateString(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            $date = new \DateTime($value);
            return $date !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica se string é JSON válido
     */
    protected static function isJsonString(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Verifica se número parece timestamp
     */
    protected static function looksLikeTimestamp(float $value): bool
    {
        // Timestamp entre 2000 e 2100 (aproximadamente)
        return $value >= 946684800 && $value <= 4102444800;
    }

    /**
     * Verifica se número parece percentual
     */
    protected static function looksLikePercentage(float $value): bool
    {
        // Entre 0 e 1 (percentual decimal) ou 0 e 100 (percentual inteiro)
        return ($value >= 0 && $value <= 1) || ($value >= 0 && $value <= 100 && $value == round($value));
    }

    /**
     * Verifica se número parece dinheiro
     */
    protected static function looksLikeMoney(float $value): bool
    {
        // Tem exatamente 2 casas decimais e está em range razoável
        return $value > 0 && $value < 1000000 && (round($value, 2) == $value);
    }

    /**
     * Verifica se número parece tamanho de arquivo
     */
    protected static function looksLikeFilesize(float $value): bool
    {
        // Números grandes que podem ser bytes
        return $value >= 1024 && $value == round($value);
    }

    /**
     * Detecta múltiplos valores e retorna formatadores sugeridos
     */
    public static function detectBatch(array $values, ?string $fieldName = null): array
    {
        $suggestions = [];
        $typeCounts = [];

        foreach ($values as $value) {
            $formatter = self::detect($value, $fieldName);
            if ($formatter) {
                $type = get_class($formatter);
                $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                $suggestions[] = [
                    'value' => $value,
                    'formatter' => $formatter,
                    'type' => $type,
                ];
            }
        }

        // Encontra o tipo mais comum
        $mostCommonType = null;
        $maxCount = 0;
        foreach ($typeCounts as $type => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $mostCommonType = $type;
            }
        }

        return [
            'suggestions' => $suggestions,
            'most_common_type' => $mostCommonType,
            'type_counts' => $typeCounts,
            'confidence' => $maxCount / count($values),
        ];
    }

    /**
     * Aplica formatação automática a um valor
     */
    public static function autoFormat(mixed $value, ?string $fieldName = null, array $context = []): string
    {
        $formatter = self::detect($value, $fieldName, $context);

        if (!$formatter) {
            return (string) $value;
        }

        try {
            return $formatter->setValue($value)->format();
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * Configurações avançadas de detecção
     */
    public static function configure(array $config): void
    {
        if (isset($config['patterns'])) {
            self::$patterns = array_merge(self::$patterns, $config['patterns']);
        }

        if (isset($config['field_hints'])) {
            self::$fieldHints = array_merge_recursive(self::$fieldHints, $config['field_hints']);
        }
    }

    /**
     * Obtém informações de debug sobre detecção
     */
    public static function debug(mixed $value, ?string $fieldName = null): array
    {
        return [
            'value' => $value,
            'value_type' => gettype($value),
            'field_name' => $fieldName,
            'detected_formatter' => get_class(self::detect($value, $fieldName) ?? new class {
                public static function class()
                {
                    return 'none';
                }
            }),
            'field_category' => self::detectFieldCategory($fieldName),
            'is_date' => self::isDateValue($value),
            'is_numeric' => is_numeric($value),
            'is_json' => is_string($value) ? self::isJsonString($value) : false,
            'string_patterns' => is_string($value) ? self::getMatchedPatterns($value) : [],
        ];
    }

    /**
     * Detecta categoria do campo
     */
    protected static function detectFieldCategory(?string $fieldName): ?string
    {
        if (!$fieldName) {
            return null;
        }

        $fieldName = strtolower($fieldName);

        foreach (self::$fieldHints as $category => $fields) {
            if (self::fieldMatchesCategory($category, $fieldName)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Obtém padrões que combinam com uma string
     */
    protected static function getMatchedPatterns(string $value): array
    {
        $matched = [];

        foreach (self::$patterns as $type => $pattern) {
            if (preg_match($pattern, $value)) {
                $matched[] = $type;
            }
        }

        return $matched;
    }
}
