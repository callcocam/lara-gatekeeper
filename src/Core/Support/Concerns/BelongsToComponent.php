<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToComponent
{

    /**
     * The component name.
     */
    protected string $component;

    /** 
     * Use  this to override the default component name.
     */
    public function component(string | Closure $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the component name.
     */
    public function getComponent(): string
    {
        return $this->evaluate($this->component)  ?? $this->getDefaultComponent();
    }
}
