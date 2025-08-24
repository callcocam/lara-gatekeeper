<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Formatters;

class NumberFormatter extends Formatter
{
    /**
     * Formatos numéricos predefinidos
     */
    public const PERCENTAGE = 'percentage';
    public const DECIMAL = 'decimal';
    public const SCIENTIFIC = 'scientific';
    public const ORDINAL = 'ordinal';
    public const FILESIZE = 'filesize';
    public const THOUSANDS = 'thousands';
    public const ABBREVIATED = 'abbreviated';

    /**
     * Unidades de tamanho de arquivo
     */
    protected static array $filesizeUnits = [
        'B', 'KB', 'MB', 'GB', 'TB', 'PB'
    ];

    /**
     * Sufixos ordinais por gênero
     */
    protected static array $ordinalSuffixes = [
        'masculino' => ['º', 'º', 'º', 'º', 'º', 'º', 'º', 'º', 'º', 'º'],
        'feminino' => ['ª', 'ª', 'ª', 'ª', 'ª', 'ª', 'ª', 'ª', 'ª', 'ª'],
    ];

    /**
     * Cria formatador de percentual
     */
    public static function percentage(int $decimals = 1): static
    {
        $instance = new static(null, ['format' => self::PERCENTAGE, 'decimals' => $decimals]);
        return $instance;
    }

    /**
     * Cria formatador decimal
     */
    public static function decimal(int $decimals = 2): static
    {
        return static::make()->config('format', self::DECIMAL)->config('decimals', $decimals);
    }

    /**
     * Cria formatador científico
     */
    public static function scientific(int $decimals = 2): static
    {
        $instance = new static(null, ['format' => self::SCIENTIFIC, 'decimals' => $decimals]);
        return $instance;
    }

    /**
     * Cria formatador ordinal (1º, 2ª, 3º)
     */
    public static function ordinal(string $gender = 'masculino'): static
    {
        $instance = new static(null, ['format' => self::ORDINAL, 'gender' => $gender]);
        return $instance;
    }

    /**
     * Cria formatador de tamanho de arquivo
     */
    public static function filesize(int $decimals = 1): static
    {
        $instance = new static(null, ['format' => self::FILESIZE, 'decimals' => $decimals]);
        return $instance;
    }

    /**
     * Cria formatador de milhares (sem decimal)
     */
    public static function thousands(): static
    {
        return static::make()->config('format', self::THOUSANDS);
    }

    /**
     * Cria formatador abreviado (1K, 1M, 1B)
     */
    public static function abbreviated(int $decimals = 1): static
    {
        $instance = new static(null, ['format' => self::ABBREVIATED, 'decimals' => $decimals]);
        return $instance;
    }

    /**
     * Executa a formatação numérica
     */
    public function format(): string
    {
        if (empty($this->value) && $this->value !== 0 && $this->value !== '0') {
            return '';
        }

        // Converte para float
        $value = $this->parseValue($this->value);
        
        if ($value === null) {
            return is_string($this->value) ? $this->value : '';
        }

        $format = $this->getConfig('format', self::DECIMAL);

        return match ($format) {
            self::PERCENTAGE => $this->formatPercentage($value),
            self::DECIMAL => $this->formatDecimal($value),
            self::SCIENTIFIC => $this->formatScientific($value),
            self::ORDINAL => $this->formatOrdinal($value),
            self::FILESIZE => $this->formatFilesize($value),
            self::THOUSANDS => $this->formatThousands($value),
            self::ABBREVIATED => $this->formatAbbreviated($value),
            default => $this->formatDecimal($value),
        };
    }

    /**
     * Converte valor para float
     */
    protected function parseValue(mixed $value): ?float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove caracteres não numéricos exceto ponto, vírgula e sinal negativo
            $cleaned = preg_replace('/[^\d.,\-+eE]/', '', $value);
            
            if (empty($cleaned)) {
                return 0.0;
            }

            // Converte vírgula para ponto (formato brasileiro)
            $cleaned = str_replace(',', '.', $cleaned);
            
            return is_numeric($cleaned) ? (float) $cleaned : null;
        }

        return null;
    }

    /**
     * Formata como percentual
     */
    protected function formatPercentage(float $value): string
    {
        $decimals = $this->getConfig('decimals', 1);
        $locale = $this->getConfig('locale', 'pt_BR');

        // Multiplica por 100 para percentual
        $percentage = $value * 100;

        if ($locale === 'pt_BR') {
            return number_format($percentage, $decimals, ',', '.') . '%';
        }

        return number_format($percentage, $decimals, '.', ',') . '%';
    }

    /**
     * Formata como decimal
     */
    protected function formatDecimal(float $value): string
    {
        $decimals = $this->getConfig('decimals', 2);
        $locale = $this->getConfig('locale', 'pt_BR');

        if ($locale === 'pt_BR') {
            return number_format($value, $decimals, ',', '.');
        }

        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Formata como notação científica
     */
    protected function formatScientific(float $value): string
    {
        $decimals = $this->getConfig('decimals', 2);
        $locale = $this->getConfig('locale', 'pt_BR');

        $formatted = sprintf('%.'. $decimals . 'E', $value);

        // Ajusta para locale brasileiro (vírgula)
        if ($locale === 'pt_BR') {
            $formatted = str_replace('.', ',', $formatted);
        }

        return $formatted;
    }

    /**
     * Formata como ordinal (1º, 2ª, 3º)
     */
    protected function formatOrdinal(float $value): string
    {
        $intValue = (int) $value;
        $gender = $this->getConfig('gender', 'masculino');

        if ($intValue < 1) {
            return (string) $intValue;
        }

        $suffix = static::$ordinalSuffixes[$gender][0] ?? 'º';

        return $intValue . $suffix;
    }

    /**
     * Formata como tamanho de arquivo
     */
    protected function formatFilesize(float $value): string
    {
        $decimals = $this->getConfig('decimals', 1);
        $locale = $this->getConfig('locale', 'pt_BR');

        if ($value == 0) {
            return '0 B';
        }

        $bytes = abs($value);
        $unitIndex = floor(log($bytes, 1024));
        $unitIndex = min($unitIndex, count(static::$filesizeUnits) - 1);
        
        $size = $bytes / pow(1024, $unitIndex);
        $unit = static::$filesizeUnits[$unitIndex];

        if ($locale === 'pt_BR') {
            $formatted = number_format($size, $decimals, ',', '.');
        } else {
            $formatted = number_format($size, $decimals, '.', ',');
        }

        return $formatted . ' ' . $unit;
    }

    /**
     * Formata milhares (sem decimais)
     */
    protected function formatThousands(float $value): string
    {
        $locale = $this->getConfig('locale', 'pt_BR');
        $intValue = (int) round($value);

        if ($locale === 'pt_BR') {
            return number_format($intValue, 0, ',', '.');
        }

        return number_format($intValue, 0, '.', ',');
    }

    /**
     * Formata de forma abreviada (1K, 1M, 1B)
     */
    protected function formatAbbreviated(float $value): string
    {
        $decimals = $this->getConfig('decimals', 1);
        $locale = $this->getConfig('locale', 'pt_BR');

        $abbreviations = [
            ['value' => 1000000000000, 'suffix' => 'T'],
            ['value' => 1000000000, 'suffix' => 'B'],
            ['value' => 1000000, 'suffix' => 'M'],
            ['value' => 1000, 'suffix' => 'K'],
        ];

        foreach ($abbreviations as $abbreviation) {
            if (abs($value) >= $abbreviation['value']) {
                $abbreviated = $value / $abbreviation['value'];
                
                if ($locale === 'pt_BR') {
                    $formatted = number_format($abbreviated, $decimals, ',', '.');
                } else {
                    $formatted = number_format($abbreviated, $decimals, '.', ',');
                }
                
                return $formatted . $abbreviation['suffix'];
            }
        }

        // Para valores menores que 1000
        if ($locale === 'pt_BR') {
            return number_format($value, $decimals, ',', '.');
        }

        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Define precisão decimal
     */
    public function decimals(int $decimals): static
    {
        return $this->config('decimals', $decimals);
    }

    /**
     * Define locale para formatação
     */
    public function locale(string $locale): static
    {
        return $this->config('locale', $locale);
    }

    /**
     * Define gênero para ordinais
     */
    public function gender(string $gender): static
    {
        return $this->config('gender', $gender);
    }

    /**
     * Define se deve mostrar sinal de positivo
     */
    public function showPositiveSign(bool $show = true): static
    {
        return $this->config('show_positive_sign', $show);
    }
} 