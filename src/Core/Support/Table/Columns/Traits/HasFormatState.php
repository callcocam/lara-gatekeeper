<?php

/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Casts\DateCast;
use Callcocam\LaraGatekeeper\Core\Support\Casts\SinceCast;
use Closure;

/**
 * Trait para aplicar formatações personalizadas a estados das colunas.
 * 
 * Disponibiliza métodos como:
 * - `since()` para exibir tempo relativo.
 * - `date()` para exibir uma data simples.
 * - `dateTime()` para exibir data e hora.
 */
trait HasFormatState
{
    protected Closure|string|null $formatState = null;

    /**
     * Aplica formato de tempo relativo (ex: "há 2 horas").
     */
    public function since(?Closure $callback = null)
    {
        $this->formatState = $callback ?: SinceCast::class;
        return $this;
    }

    /**
     * Aplica formato de data simples (ex: "2024-12-06").
     */
    public function date(?Closure $callback = null)
    {
        $this->formatState = $callback ?: DateCast::class;
        return $this;
    }

    /**
     * Aplica formato de data e hora (ex: "2024-12-06 14:00:00").
     */
    public function dateTime(?Closure $callback = null)
    {
        $this->formatState = $callback ?: DateCast::class;
        return $this;
    }

    /**
     * Avalia e retorna o estado formatado.
     */
    public function getFormatState()
    {
        return $this->evaluate($this->formatState);
    }

    /**
     * Verifica se o estado está configurado para ser formatado.
     */
    public function isCast()
    {
        return !is_null($this->formatState);
    }
}
