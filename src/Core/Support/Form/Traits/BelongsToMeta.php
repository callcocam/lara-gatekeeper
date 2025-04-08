<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */ 
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Traits;

trait BelongsToMeta
{
    protected array $meta = [];

    public function meta(array $value): static
    {
        $this->meta = array_merge($this->meta, $value);
        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
} 