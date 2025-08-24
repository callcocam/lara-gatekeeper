<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Formatters;

use Carbon\Carbon;

class DateFormatter extends Formatter
{
    /**
     * Formatos predefinidos
     */
    public const RELATIVE = 'relative';
    public const DATETIME = 'datetime';
    public const DATE = 'date';
    public const TIME = 'time';
    public const CUSTOM = 'custom';

    /**
     * Cria formatador de data relativa (ex: "2 horas atrás")
     */
    public static function relative(): static
    {
        return static::make()->config('format', self::RELATIVE);
    }

    /**
     * Cria formatador de data e hora completa
     */
    public static function dateTime(string $format = 'd/m/Y H:i'): static
    {
        return static::make()->config('format', self::DATETIME)->config('pattern', $format);
    }

    /**
     * Cria formatador apenas de data
     */
    public static function date(string $format = 'd/m/Y'): static
    {
        return static::make()->config('format', self::DATE)->config('pattern', $format);
    }

    /**
     * Cria formatador apenas de hora
     */
    public static function time(string $format = 'H:i'): static
    {
        return static::make()->config('format', self::TIME)->config('pattern', $format);
    }

    /**
     * Cria formatador customizado
     */
    public static function custom(string $format): static
    {
        return static::make()->config('format', self::CUSTOM)->config('pattern', $format);
    }

    /**
     * Executa a formatação da data
     */
    public function format(): string
    {
        if (empty($this->value)) {
            return '';
        }

        // Tenta converter para Carbon
        try {
            $date = $this->parseDate($this->value);
        } catch (\Exception $e) {
            return is_string($this->value) ? $this->value : '';
        }

        $format = $this->getConfig('format', self::DATETIME);

        return match ($format) {
            self::RELATIVE => $this->formatRelative($date),
            self::DATETIME => $this->formatDateTime($date),
            self::DATE => $this->formatDate($date),
            self::TIME => $this->formatTime($date),
            self::CUSTOM => $this->formatCustom($date),
            default => $this->formatDateTime($date),
        };
    }

    /**
     * Converte valor para Carbon
     */
    protected function parseDate(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return Carbon::instance($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        if (is_int($value)) {
            return Carbon::createFromTimestamp($value);
        }

        throw new \InvalidArgumentException('Não foi possível converter valor para data');
    }

    /**
     * Formata data relativamente ("2 horas atrás")
     */
    protected function formatRelative(Carbon $date): string
    {
        // Define locale para português se disponível
        try {
            $date->locale('pt_BR');
        } catch (\Exception $e) {
            // Fallback para inglês se pt_BR não estiver disponível
            $date->locale('en');
        }

        return $date->diffForHumans();
    }

    /**
     * Formata data e hora
     */
    protected function formatDateTime(Carbon $date): string
    {
        $pattern = $this->getConfig('pattern', 'd/m/Y H:i');
        return $date->format($pattern);
    }

    /**
     * Formata apenas data
     */
    protected function formatDate(Carbon $date): string
    {
        $pattern = $this->getConfig('pattern', 'd/m/Y');
        return $date->format($pattern);
    }

    /**
     * Formata apenas hora
     */
    protected function formatTime(Carbon $date): string
    {
        $pattern = $this->getConfig('pattern', 'H:i');
        return $date->format($pattern);
    }

    /**
     * Formata com padrão customizado
     */
    protected function formatCustom(Carbon $date): string
    {
        $pattern = $this->getConfig('pattern', 'd/m/Y H:i');
        return $date->format($pattern);
    }

    /**
     * Define timezone para formatação
     */
    public function timezone(string $timezone): static
    {
        return $this->config('timezone', $timezone);
    }

    /**
     * Define locale para formatação
     */
    public function locale(string $locale): static
    {
        return $this->config('locale', $locale);
    }
} 