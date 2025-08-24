<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToId
{
    /**
     * The ID of the column.
     *
     * @var string|null
     */
    public Closure|string|null $id = null;

    /**
     * Set the ID for the column.
     *
     * @param string $id
     * @return static
     */
    public function id(Closure|string|null $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the ID of the column.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->evaluate($this->id);
    }

    /**
     * Has an ID been set for the column?
     *
     * @return bool
     */
    public function hasId(): bool
    {
        return !is_null($this->id);
    }
}
