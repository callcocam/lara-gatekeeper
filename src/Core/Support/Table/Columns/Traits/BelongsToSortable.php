<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

trait BelongsToSortable
{
    protected bool $sortable = false;

    public function sortable(bool $value = true): static
    {
        $this->sortable = $value;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }
} 