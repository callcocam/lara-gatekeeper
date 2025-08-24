<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Formatters;

use NumberFormatter as PHPNumberFormatter;

class MoneyFormatter extends Formatter
{
    /**
     * Formatos de moeda predefinidos
     */
    public const BRL = 'BRL';
    public const USD = 'USD';
    public const EUR = 'EUR';
    public const DECIMAL = 'decimal';
    public const CENTS = 'cents';

    /**
     * Símbolos de moedas
     */
    protected static array $currencySymbols = [
        'BRL' => 'R$',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
    ];

    /**
     * Locales para formatação
     */
    protected static array $locales = [
        'BRL' => 'pt_BR',
        'USD' => 'en_US',
        'EUR' => 'de_DE',
        'GBP' => 'en_GB',
        'JPY' => 'ja_JP',
    ];

    /**
     * Cria formatador de moeda BRL
     */
    public static function brl(): static
    {
        return static::make()->config('currency', self::BRL)->config('locale', 'pt_BR');
    }

    /**
     * Cria formatador de moeda USD
     */
    public static function usd(): static
    {
        return static::make()->config('currency', self::USD)->config('locale', 'en_US');
    }

    /**
     * Cria formatador de moeda EUR
     */
    public static function eur(): static
    {
        return static::make()->config('currency', self::EUR)->config('locale', 'de_DE');
    }

    /**
     * Cria formatador de moeda customizada
     */
    public static function currency(string $currency, ?string $locale = null): static
    {
        $locale = $locale ?? static::$locales[$currency] ?? 'pt_BR';
        return static::make()->config('currency', $currency)->config('locale', $locale);
    }

    /**
     * Cria formatador decimal (sem símbolo de moeda)
     */
    public static function decimal(int $decimals = 2): static
    {
        return static::make()->config('format', self::DECIMAL)->config('decimals', $decimals);
    }

    /**
     * Cria formatador para centavos (converte 1234 centavos para R$ 12,34)
     */
    public static function cents(string $currency = 'BRL'): static
    {
        return static::make()->config('format', self::CENTS)->config('currency', $currency);
    }

    /**
     * Executa a formatação monetária
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

        $format = $this->getConfig('format', 'currency');

        return match ($format) {
            self::DECIMAL => $this->formatDecimal($value),
            self::CENTS => $this->formatCents($value),
            default => $this->formatCurrency($value),
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
            // Remove caracteres não numéricos exceto ponto e vírgula
            $cleaned = preg_replace('/[^\d.,\-]/', '', $value);
            
            // Se está vazio após limpeza
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
     * Formata como moeda
     */
    protected function formatCurrency(float $value): string
    {
        $currency = $this->getConfig('currency', 'BRL');
        $locale = $this->getConfig('locale', 'pt_BR');
        
        // Tenta usar NumberFormatter do PHP se disponível
        if (class_exists(PHPNumberFormatter::class)) {
            try {
                $formatter = new PHPNumberFormatter($locale, PHPNumberFormatter::CURRENCY);
                return $formatter->formatCurrency($value, $currency);
            } catch (\Exception $e) {
                // Fallback para formatação manual
            }
        }

        // Formatação manual para BRL
        if ($currency === 'BRL') {
            return 'R$ ' . number_format($value, 2, ',', '.');
        }

        // Formatação manual para USD
        if ($currency === 'USD') {
            return '$' . number_format($value, 2, '.', ',');
        }

        // Formatação manual para EUR
        if ($currency === 'EUR') {
            return '€' . number_format($value, 2, ',', '.');
        }

        // Formatação genérica
        $symbol = static::$currencySymbols[$currency] ?? $currency;
        return $symbol . ' ' . number_format($value, 2, '.', ',');
    }

    /**
     * Formata como decimal (sem símbolo de moeda)
     */
    protected function formatDecimal(float $value): string
    {
        $decimals = $this->getConfig('decimals', 2);
        $locale = $this->getConfig('locale', 'pt_BR');

        // Para locale brasileiro, usa vírgula como separador decimal
        if ($locale === 'pt_BR') {
            return number_format($value, $decimals, ',', '.');
        }

        // Para outros locales, usa ponto como separador decimal
        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Formata centavos (1234 centavos = R$ 12,34)
     */
    protected function formatCents(float $value): string
    {
        // Converte centavos para reais (divide por 100)
        $realValue = $value / 100;
        
        $currency = $this->getConfig('currency', 'BRL');
        
        // Temporarily set the format to currency for formatting
        $originalFormat = $this->getConfig('format');
        $this->config('format', 'currency');
        
        $result = $this->formatCurrency($realValue);
        
        // Restore original format
        $this->config('format', $originalFormat);
        
        return $result;
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
     * Define símbolo customizado
     */
    public function symbol(string $symbol): static
    {
        return $this->config('symbol', $symbol);
    }

    /**
     * Mostra valor negativo entre parênteses
     */
    public function parenthesesForNegative(bool $enabled = true): static
    {
        return $this->config('parentheses_negative', $enabled);
    }
} 