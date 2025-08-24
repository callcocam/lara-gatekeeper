<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToIcon
{
    protected Closure|string $icon = 'Settings';

    protected Closure|string $iconPosition = 'left';

    /**
     * Define o ícone da ação
     */
    public function icon(Closure|string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Obtém o ícone da ação
     */
    public function getIcon(): string
    {
        return $this->evaluate($this->icon);
    }

    /**
     * Verifica se o ícone existe
     */
    public function hasIcon(): bool
    {
        return !empty($this->icon);
    }

    /**
     * Retorna o ícone como string
     */
    public function toStringIcon(): string
    {
        return is_string($this->icon) ? $this->icon : '';
    }

    /**
     * Define a posição do ícone
     */
    public function iconPosition(Closure|string $position): static
    {
        $this->iconPosition = $position;
        return $this;
    }

    /**
     * Obtém a posição do ícone
     */
    public function getIconPosition(): string
    {
        return $this->evaluate($this->iconPosition);
    }
 
}
