<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Formatters;

use Callcocam\LaraGatekeeper\Core\Concerns\EvaluatesClosures;

abstract class Formatter
{
    use EvaluatesClosures;

    /**
     * O valor original a ser formatado
     */
    protected mixed $value;

    /**
     * Configurações do formatador
     */
    protected array $config = [];

    /**
     * Dados do registro (para contexto)
     */
    protected mixed $record = null;

    public function __construct(mixed $value = null, array $config = [])
    {
        $this->value = $value;
        $this->config = $config;
    }

    /**
     * Factory method para criar instância
     */
    public static function make(mixed $value = null, array $config = []): static
    {
        return new static($value, $config);
    }

    /**
     * Define valor para formatação (preservando configurações)
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Define o registro para contexto adicional
     */
    public function setRecord(mixed $record): static
    {
        $this->record = $record;
        return $this;
    }

    /**
     * Define uma configuração
     */
    public function config(string $key, mixed $value): static
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Obtém uma configuração
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->evaluate($this->config[$key] ?? $default, [
            'record' => $this->record,
            'value' => $this->value,
        ]);
    }

    /**
     * Método principal de formatação (deve ser implementado pelas classes filhas)
     */
    abstract public function format(): string;

    /**
     * Converte para string (atalho para format())
     */
    public function __toString(): string
    {
        try {
            return $this->format();
        } catch (\Exception $e) {
            // Em caso de erro, retorna o valor original ou string vazia
            return is_string($this->value) ? $this->value : '';
        }
    }

    /**
     * Converte para array (útil para debugging)
     */
    public function toArray(): array
    {
        return [
            'formatted' => $this->format(),
            'original' => $this->value,
            'config' => $this->config,
            'formatter' => static::class,
        ];
    }
} 