<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToColor
{
    protected Closure|string $color = 'gray';

    /**
     * Define a cor da ação
     */
    public function color(Closure|string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Obtém a cor da ação
     */
    public function getColor(): string
    {
        return $this->evaluate($this->color);
    }

    /**
     * Verifica se a cor está definida
     */
    public function hasColor(): bool
    {
        return !empty($this->color);
    }
}
