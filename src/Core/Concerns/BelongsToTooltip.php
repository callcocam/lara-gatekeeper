<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToTooltip
{
    protected Closure|string $tooltip = '';

    /**
     * Define o tooltip da ação
     */
    public function tooltip(Closure|string $tooltip): static
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    /**
     * Obtém o tooltip da ação
     */
    public function getTooltip(): string
    {
        return $this->evaluate($this->tooltip);
    }

    /**
     * Verifica se o tooltip está definido
     */
    public function hasTooltip(): bool
    {
        return !empty($this->tooltip);
    }
}
