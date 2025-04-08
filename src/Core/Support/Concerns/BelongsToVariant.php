<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait BelongsToVariant
{
    protected string $variant = 'default';

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function getVariant(): string
    {
        return $this->variant;
    }
} 