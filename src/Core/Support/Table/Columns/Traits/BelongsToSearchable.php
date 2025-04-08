<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

trait BelongsToSearchable
{
    protected bool $searchable = false;

    public function searchable(bool $value = true): static
    {
        $this->searchable = $value;
        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }
} 