<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Concerns;


use Closure;

trait HasSortable
{
    /**
     * The sortable state of the column.
     *
     * @var bool
     */
    protected Closure|bool $sortable = false;

    /**
     * Set the column as sortable.
     *
     * @param bool $sortable
     * @return static
     */
    public function sortable(Closure|bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * Check if the column is sortable.
     *
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->evaluate($this->sortable);
    }

    /**
     * Get the sortable state of the column.
     *
     * @return bool
     */
    public function getSortable(): bool
    {
        return $this->evaluate($this->sortable);
    }
}
