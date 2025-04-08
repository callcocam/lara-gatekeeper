<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */ 
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Traits;

trait BelongsToDefault
{
    protected mixed $default = null;

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }
} 