<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */ 
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Traits;

trait BelongsToRequired
{
    protected bool $required = false;

    public function required(bool $value = true): static
    {
        $this->required = $value;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
} 