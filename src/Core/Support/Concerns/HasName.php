<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasName
{
    /**
     * Set field name (useful when different from key)
     * 
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set field ID attribute
     * 
     * @param string $id
     * @return $this
     */
    public function id(string $id): self
    {
        $this->inputProps['id'] = $id;
        return $this;
    }

    /**
     * Add data attributes to field
     * 
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function dataAttribute(string $key, mixed $value): self
    {
        if (!isset($this->inputProps['dataAttributes'])) {
            $this->inputProps['dataAttributes'] = [];
        }
        
        $this->inputProps['dataAttributes'][$key] = $value;
        return $this;
    }

    /**
     * Set field wire:model attribute (useful for Livewire)
     * 
     * @param string $model
     * @param string|null $modifier Optional modifier like .defer, .lazy, .debounce
     * @return $this
     */
    public function wireModel(string $model, ?string $modifier = null): self
    {
        $wireModelName = $modifier ? "wire:model{$modifier}" : "wire:model";
        $this->inputProps[$wireModelName] = $model;
        return $this;
    }

    /**
     * Set field x-model attribute (useful for Alpine.js)
     * 
     * @param string $model
     * @return $this
     */
    public function xModel(string $model): self
    {
        $this->inputProps['x-model'] = $model;
        return $this;
    }

    /**
     * Set field's name attribute with array notation
     * 
     * @param string $parent Parent field name
     * @return $this
     */
    public function arrayName(string $parent): self
    {
        $this->name = $parent . '[' . $this->key . ']';
        return $this;
    }

    /**
     * Set field to be part of a form array
     * 
     * @param string $arrayName
     * @param int|string|null $index If not provided, uses the field key
     * @return $this
     */
    public function inArray(string $arrayName, int|string|null $index = null): self
    {
        $index = $index ?? $this->key;
        $this->name = "{$arrayName}[{$index}]";
        return $this;
    }
}