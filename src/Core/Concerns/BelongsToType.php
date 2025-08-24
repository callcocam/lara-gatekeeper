<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToType
{
    /**
     * The type of the column.
     *
     * @var string|null
     */
    protected Closure|string|null $type = 'text';

    /**
     * Set the type for the column.
     *
     * @param string $type
     * @return static
     */
    public function type(Closure|string|null $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set the type for the column.
     *
     * @param string $type
     * @return static
     */
    public function setType(Closure|string|null $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the type of the column.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->evaluate($this->type);
    }

    /**
     * Has an ID been set for the column?
     *
     * @return bool
     */
    public function hasType(): bool
    {
        return !is_null($this->type);
    }
}
