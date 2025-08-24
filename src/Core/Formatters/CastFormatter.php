<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Formatters;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes; 


class CastFormatter extends Formatter
{
    /**
     * Tipos de cast predefinidos
     */
    public const AUTO = 'auto';
    public const CUSTOM_CLASS = 'custom_class';
    public const ELOQUENT_CAST = 'eloquent_cast';
    public const CONDITIONAL = 'conditional';
    public const CLOSURE = 'closure';
    public const ENUM = 'enum';
    public const JSON = 'json';
    public const BOOLEAN = 'boolean';
    public const PHONE = 'phone';

    /**
     * Cria formatador com cast automático
     */
    public static function auto(mixed $value = null): static
    { 
        $instance = new static($value, ['cast_type' => self::AUTO]);
        return $instance;
    }

    /**
     * Cria formatador com cast class customizada
     */
    public static function castClass(string $castClass): static
    {
        $instance = new static(null, [
            'cast_type' => self::CUSTOM_CLASS,
            'cast_class' => $castClass
        ]);
        return $instance;
    }

    /**
     * Cria formatador usando cast do Eloquent
     */
    public static function eloquentCast(string $attribute, mixed $model = null): static
    {
        $instance = new static(null, [
            'cast_type' => self::ELOQUENT_CAST,
            'attribute' => $attribute,
            'model' => $model
        ]);
        return $instance;
    }

    /**
     * Cria formatador condicional baseado em regras
     */
    public static function conditional(array $conditions): static
    {
        $instance = new static(null, [
            'cast_type' => self::CONDITIONAL,
            'conditions' => $conditions
        ]);
        return $instance;
    }

    /**
     * Cria formatador usando closure
     */
    public static function closure(\Closure $closure): static
    {
        $instance = new static(null, [
            'cast_type' => self::CLOSURE,
            'closure' => $closure
        ]);
        return $instance;
    }

    /**
     * Cria formatador para enums
     */
    public static function enum(string $enumClass): static
    {
        $instance = new static(null, [
            'cast_type' => self::ENUM,
            'enum_class' => $enumClass
        ]);
        return $instance;
    }

    /**
     * Cria formatador para JSON
     */
    public static function json(bool $pretty = false): static
    {
        $instance = new static(null, [
            'cast_type' => self::JSON,
            'pretty' => $pretty
        ]);
        return $instance;
    }

    /**
     * Cria formatador para telefone
     */
    public static function phone(?string $value): static
    {
        $instance = new static($value, [
            'cast_type' => self::PHONE
        ]);
        return $instance;
    }

    /**
     * Cria formatador para booleanos
     */
    public static function boolean(string $trueLabel = 'Sim', string $falseLabel = 'Não'): static
    {
        $instance = new static(null, [
            'cast_type' => self::BOOLEAN,
            'true_label' => $trueLabel,
            'false_label' => $falseLabel
        ]);
        return $instance;
    }

    /**
     * Executa a formatação usando cast
     */
    public function format(): string
    {
        if ($this->value === null) {
            return '';
        }

        $castType = $this->getConfig('cast_type', self::AUTO);

        return match ($castType) {
            self::AUTO => $this->formatAuto(),
            self::CUSTOM_CLASS => $this->formatCustomClass(),
            self::ELOQUENT_CAST => $this->formatEloquentCast(),
            self::CONDITIONAL => $this->formatConditional(),
            self::CLOSURE => $this->formatClosure(),
            self::ENUM => $this->formatEnum(),
            self::JSON => $this->formatJson(),
            self::BOOLEAN => $this->formatBoolean(),
            default => $this->formatAuto(),
        };
    }

    /**
     * Formatação automática baseada no tipo do valor
     */
    protected function formatAuto(): string
    {
        if (is_bool($this->value)) {
            return $this->formatBoolean();
        }

        if (is_array($this->value) || is_object($this->value)) {
            return $this->formatJson();
        }

        if (is_string($this->value) && $this->isJson($this->value)) {
            return $this->formatJson();
        }

        if (is_numeric($this->value)) {
            // Se é inteiro grande, pode ser timestamp
            if (is_int($this->value) && $this->value > 946684800) { // > 01/01/2000
                return DateFormatter::dateTime()->setValue($this->value)->format();
            }

            // Se é float com 2 decimais, pode ser dinheiro
            if (is_float($this->value) && $this->hasMoneyPattern($this->value)) {
                return MoneyFormatter::brl()->setValue($this->value)->format();
            }

            return NumberFormatter::decimal(2)->setValue($this->value)->format();
        }

        if (is_string($this->value) && $this->isDate($this->value)) {
            return DateFormatter::dateTime()->setValue($this->value)->format();
        }

        // Para strings simples, retorna como está
        return (string) $this->value;
    }

    /**
     * Formatação usando cast class customizada
     */
    protected function formatCustomClass(): string
    {
        $castClass = $this->getConfig('cast_class');
        
        if (!class_exists($castClass)) {
            return (string) $this->value;
        }

        try {
            $caster = new $castClass();
            
            if ($caster instanceof CastsAttributes) {
                $result = $caster->get(null, 'attribute', $this->value, []);
            } elseif (method_exists($caster, 'format')) {
                $result = $caster->format($this->value, $this->record);
            } elseif (method_exists($caster, '__toString')) {
                $caster->value = $this->value;
                $result = (string) $caster;
            } else {
                $result = $this->value;
            }

            return (string) $result;
        } catch (\Exception $e) {
            return (string) $this->value;
        }
    }

    /**
     * Formatação usando cast do Eloquent
     */
    protected function formatEloquentCast(): string
    {
        $model = $this->getConfig('model');
        $attribute = $this->getConfig('attribute');

        if (!$model || !$attribute) {
            return (string) $this->value;
        }

        try {
            // Tenta usar cast do modelo se disponível
            if (is_object($model) && method_exists($model, 'getCasts')) {
                $casts = $model->getCasts();
                if (isset($casts[$attribute])) {
                    $model->setAttribute($attribute, $this->value);
                    $result = $model->getAttribute($attribute);
                    return (string) $result;
                }
            }

            return (string) $this->value;
        } catch (\Exception $e) {
            return (string) $this->value;
        }
    }

    /**
     * Formatação condicional baseada em regras
     */
    protected function formatConditional(): string
    {
        $conditions = $this->getConfig('conditions', []);

        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($condition)) {
                $format = $condition['format'];
                
                if (is_callable($format)) {
                    return (string) $format($this->value, $this->record);
                }
                
                if (is_string($format)) {
                    return str_replace(['{value}', '{record}'], [$this->value, json_encode($this->record)], $format);
                }
                
                return $this->evaluate($format, [
                    'value' => $this->value,
                    'record' => $this->record,
                ]);
            }
        }

        // Se nenhuma condição foi atendida, retorna valor original
        return (string) $this->value;
    }

    /**
     * Formatação usando closure
     */
    protected function formatClosure(): string
    {
        $closure = $this->getConfig('closure');

        if (!$closure instanceof \Closure) {
            return (string) $this->value;
        }

        try {
            // Tenta usar o closure diretamente
            $result = $closure($this->value, $this->record);

            return (string) $result;
        } catch (\Exception $e) {
            try {
                // Se falhar, tenta apenas com o valor
                $result = $closure($this->value);
                return (string) $result;
            } catch (\Exception $e2) {
                return (string) $this->value;
            }
        }
    }

    /**
     * Formatação para enums
     */
    protected function formatEnum(): string
    {
        $enumClass = $this->getConfig('enum_class');

        if (!enum_exists($enumClass)) {
            return (string) $this->value;
        }

        try {
            $enum = $enumClass::from($this->value);
            
            // Se o enum tem método name ou label
            if (method_exists($enum, 'label')) {
                return $enum->label();
            }
            
            if (method_exists($enum, 'name')) {
                return $enum->name();
            }

            // Se é BackedEnum
            if ($enum instanceof \BackedEnum) {
                return (string) $enum->value;
            }

            return $enum->name;
        } catch (\Exception $e) {
            return (string) $this->value;
        }
    }

    /**
     * Formatação para JSON
     */
    protected function formatJson(): string
    {
        $pretty = $this->getConfig('pretty', false);

        try {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
            } else {
                $data = $this->value;
            }

            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            if ($pretty) {
                $flags |= JSON_PRETTY_PRINT;
            }

            return json_encode($data, $flags) ?: (string) $this->value;
        } catch (\Exception $e) {
            return (string) $this->value;
        }
    }

    /**
     * Formatação para booleanos
     */
    protected function formatBoolean(): string
    {
        $trueLabel = $this->getConfig('true_label', 'Sim');
        $falseLabel = $this->getConfig('false_label', 'Não');

        if (is_bool($this->value)) {
            return $this->value ? $trueLabel : $falseLabel;
        }

        // Converte string/int para boolean
        $boolValue = filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        
        if ($boolValue === null) {
            return (string) $this->value;
        }

        return $boolValue ? $trueLabel : $falseLabel;
    }

    /**
     * Verifica se uma condição é atendida
     */
    protected function evaluateCondition(array $condition): bool
    {
        if (!isset($condition['condition'])) {
            return false;
        }

        $conditionCheck = $condition['condition'];

        if (is_callable($conditionCheck)) {
            $result = $this->evaluate($conditionCheck, [
                'value' => $this->value,
                'record' => $this->record,
            ]);
            return (bool) $result;
        }

        // Condições simples
        if (is_array($conditionCheck)) {
            $operator = $conditionCheck['operator'] ?? '==';
            $compareValue = $conditionCheck['value'] ?? null;

            return match ($operator) {
                '==' => $this->value == $compareValue,
                '===' => $this->value === $compareValue,
                '!=' => $this->value != $compareValue,
                '!==' => $this->value !== $compareValue,
                '>' => $this->value > $compareValue,
                '>=' => $this->value >= $compareValue,
                '<' => $this->value < $compareValue,
                '<=' => $this->value <= $compareValue,
                'in' => in_array($this->value, (array) $compareValue),
                'not_in' => !in_array($this->value, (array) $compareValue),
                default => false,
            };
        }

        return false;
    }

    /**
     * Verifica se o valor é JSON válido
     */
    protected function isJson(string $value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Verifica se o valor parece ser uma data
     */
    protected function isDate(string $value): bool
    {
        return (bool) strtotime($value);
    }

    /**
     * Verifica se o valor tem padrão monetário
     */
    protected function hasMoneyPattern(float $value): bool
    {
        // Se tem exatamente 2 casas decimais e está em um range razoável
        return $value > 0 && $value < 1000000 && (round($value, 2) == $value);
    }

    /**
     * Define condições para formatação condicional
     */
    public function when($condition, $format): static
    {
        $conditions = $this->getConfig('conditions', []);
        $conditions[] = [
            'condition' => $condition,
            'format' => $format,
        ];
        
        return $this->config('conditions', $conditions);
    }

    /**
     * Define labels para booleanos
     */
    public function booleanLabels(string $trueLabel, string $falseLabel): static
    {
        return $this->config('true_label', $trueLabel)
                   ->config('false_label', $falseLabel);
    }

    /**
     * Define se JSON deve ser formatado com pretty print
     */
    public function prettyJson(bool $pretty = true): static
    {
        return $this->config('pretty', $pretty);
    }
} 