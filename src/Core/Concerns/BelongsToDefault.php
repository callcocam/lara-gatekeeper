<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

trait BelongsToDefault
{
    /**
     * The default value for the filter.
     *
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * Set the default value for the filter.
     *
     * @param mixed $default
     * @return static
     */
    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Get the default value for the filter.
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->evaluate($this->default);
    }
}
