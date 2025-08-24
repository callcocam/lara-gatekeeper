<?php

namespace Callcocam\LaraGatekeeper\Core\Cast;

use Carbon\Carbon;
use Callcocam\LaraGatekeeper\Core\Formatters\DateFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\MoneyFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\NumberFormatter;
use Callcocam\LaraGatekeeper\Core\Formatters\CastFormatter;

/**
 * SimpleAutoDetector - Versão simplificada com prioridades claras
 */
class SimpleAutoDetector
{
    /**
     * Ordem de prioridade na detecção:
     * 1. Campo específico (maior prioridade)
     * 2. Padrão no valor
     * 3. Tipo do valor
     */
    
    /**
     * Campos específicos com seus formatadores (PRIORIDADE MÁXIMA)
     */
    protected static array $specificFields = [
        // Campos de data/hora
        'created_at' => 'datetime',
        'updated_at' => 'datetime', 
        'deleted_at' => 'datetime',
        'published_at' => 'datetime',
        
        // Campos que NÃO devem ser formatados (manter originais)
        'email' => 'preserve',
        'phone' => 'preserve', 
        'cnpj' => 'preserve',
        'cpf' => 'preserve',
        'cep' => 'preserve',
        
        // Campos monetários
        'price' => 'money',
        'cost' => 'money',
        'value' => 'money',
        'amount' => 'money',
        'salary' => 'money',
        'total' => 'money',
        'balance' => 'money',
        
        // Campos booleanos
        'active' => 'boolean',
        'enabled' => 'boolean',
        'published' => 'boolean',
        'verified' => 'boolean',
        
        // Campos JSON
        'metadata' => 'json',
        'settings' => 'json',
        'config' => 'json',
    ];

    /**
     * Padrões no nome do campo (PRIORIDADE MÉDIA)
     */
    protected static array $fieldPatterns = [
        // Campos que devem ser preservados
        '/email/' => 'preserve',
        '/phone|mobile|celular/' => 'preserve',
        '/cnpj|cpf|documento/' => 'preserve',
        '/cep|zipcode/' => 'preserve',
        
        // Campos de data
        '/_at$|_date$/' => 'datetime',
        '/data_|date_/' => 'datetime',
        
        // Campos monetários  
        '/price|preco|valor|custo|salario/' => 'money',
        
        // Campos booleanos
        '/^is_|^has_|^can_|ativo|habilitado/' => 'boolean',
        
        // Campos percentuais
        '/percent|taxa|desconto/' => 'percentage',
        
        // Campos de contagem
        '/count|total|qtd|quantidade/' => 'count',
        
        // Campos JSON
        '/json|meta|config|settings/' => 'json',
    ];

    /**
     * Detecta automaticamente o formatador apropriado
     */
    public static function detect(mixed $value, ?string $fieldName = null, array $context = []): ?object
    {
        // Valor vazio retorna null
        if (static::isEmpty($value)) {
            return null;
        }

        // 1. PRIORIDADE MÁXIMA: Campo específico
        if ($fieldName) {
            $formatter = static::detectSpecificField($fieldName, $value);
            if ($formatter) {
                return $formatter;
            }
        }

        // 2. PRIORIDADE MÉDIA: Padrão no nome do campo
        if ($fieldName) {
            $formatter = static::detectFieldPattern($fieldName, $value);
            if ($formatter) {
                return $formatter;
            }
        }

        // 3. PRIORIDADE BAIXA: Tipo/conteúdo do valor
        return static::detectByValueType($value);
    }

    /**
     * Detecta por campo específico
     */
    protected static function detectSpecificField(string $fieldName, mixed $value): ?object
    {
        $fieldName = strtolower($fieldName);
        
        if (!isset(static::$specificFields[$fieldName])) {
            return null;
        }

        $type = static::$specificFields[$fieldName];
        
        return static::createFormatter($type, $value);
    }

    /**
     * Detecta por padrão no nome do campo
     */
    protected static function detectFieldPattern(string $fieldName, mixed $value): ?object
    {
        $fieldName = strtolower($fieldName);
        
        foreach (static::$fieldPatterns as $pattern => $type) {
            if (preg_match($pattern, $fieldName)) {
                return static::createFormatter($type, $value);
            }
        }
        
        return null;
    }

    /**
     * Detecta por tipo do valor
     */
    protected static function detectByValueType(mixed $value): ?object
    {
        // Boolean
        if (is_bool($value)) {
            return static::createFormatter('boolean', $value);
        }

        // Array/Object
        if (is_array($value) || is_object($value)) {
            return static::createFormatter('json', $value);
        }

        // Data/Carbon
        if ($value instanceof \DateTimeInterface || $value instanceof Carbon) {
            return static::createFormatter('datetime', $value);
        }

        // String - verifica conteúdo
        if (is_string($value)) {
            // JSON
            if (static::isJson($value)) {
                return static::createFormatter('json', $value);
            }
            
            // Data
            if (static::isDateString($value)) {
                return static::createFormatter('datetime', $value);
            }
            
            // Não formatar strings por padrão
            return null;
        }

        // Números - detecta padrões específicos
        if (is_numeric($value)) {
            $numValue = (float) $value;
            
            // Timestamp
            if (static::looksLikeTimestamp($numValue)) {
                return static::createFormatter('datetime', $value);
            }
            
            // Dinheiro (tem 2 decimais e está em range razoável)
            if (static::looksLikeMoney($numValue)) {
                return static::createFormatter('money', $value);
            }
            
            // Percentual (entre 0 e 1)
            if ($numValue >= 0 && $numValue <= 1) {
                return static::createFormatter('percentage', $value);
            }
            
            // Número grande (abreviar)
            if ($numValue >= 10000) {
                return static::createFormatter('count', $value);
            }
            
            // Número comum
            return static::createFormatter('number', $value);
        }

        return null;
    }

    /**
     * Cria o formatador baseado no tipo
     */
    protected static function createFormatter(string $type, mixed $value): ?object
    {
        return match($type) {
            'preserve' => null, // Não formatar, preservar original
            'datetime' => DateFormatter::relative()->setValue($value),
            'money' => MoneyFormatter::brl()->setValue($value),
            'boolean' => CastFormatter::boolean('Sim', 'Não')->setValue($value),
            'json' => CastFormatter::json()->setValue($value),
            'percentage' => NumberFormatter::percentage()->setValue($value),
            'count' => NumberFormatter::abbreviated()->setValue($value),
            'number' => NumberFormatter::decimal(2)->setValue($value),
            default => null,
        };
    }

    /**
     * Formata automaticamente um valor
     */
    public static function autoFormat(mixed $value, ?string $fieldName = null, array $context = []): string
    {
        $formatter = static::detect($value, $fieldName, $context);
        
        if (!$formatter) {
            return (string) $value; // Retorna valor original se não há formatador
        }

        try {
            return $formatter->format();
        } catch (\Exception $e) {
            return (string) $value; // Em caso de erro, retorna valor original
        }
    }

    /**
     * Verifica se valor está vazio
     */
    protected static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Verifica se string é JSON válido
     */
    protected static function isJson(string $value): bool
    {
        if (empty($value) || !in_array($value[0], ['{', '['])) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Verifica se string é data válida
     */
    protected static function isDateString(string $value): bool
    {
        try {
            return (bool) strtotime($value);
        } catch (\Exception $e) {
            return false;
        }
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
     * Verifica se número parece dinheiro
     */
    protected static function looksLikeMoney(float $value): bool
    {
        // Tem exatamente 2 casas decimais e está em range razoável
        return $value > 0 && $value < 1000000 && (round($value, 2) == $value);
    }

    /**
     * Configurações específicas para campos que devem ser preservados
     */
    public static function addPreservedField(string $fieldName): void
    {
        static::$specificFields[strtolower($fieldName)] = 'preserve';
    }
    
    /**
     * Remove um campo da lista de preservados
     */
    public static function removePreservedField(string $fieldName): void
    {
        unset(static::$specificFields[strtolower($fieldName)]);
    }
    
    /**
     * Lista todos os campos preservados
     */
    public static function getPreservedFields(): array
    {
        return array_keys(array_filter(
            static::$specificFields, 
            fn($type) => $type === 'preserve'
        ));
    }
}