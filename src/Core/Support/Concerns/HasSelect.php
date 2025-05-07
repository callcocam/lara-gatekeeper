<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasSelect
{
    /**
     * Enable multiple selection
     * 
     * @param bool $multiSelect
     * @return $this
     */
    public function multiSelect(bool $multiSelect = true): self
    {
        $this->inputProps['multiple'] = $multiSelect;
        return $this;
    }

    /**
     * Enable searchable option
     * 
     * @param bool $searchable
     * @return $this
     */
    public function searchable(bool $searchable = true): self
    {
        $this->inputProps['searchable'] = $searchable;
        return $this;
    }

    /**
     * Enable clearable option
     * 
     * @param bool $clearable
     * @return $this
     */
    public function clearable(bool $clearable = true): self
    {
        $this->inputProps['clearable'] = $clearable;
        return $this;
    }

    /**
     * Set placeholder text
     * 
     * @param string $placeholder
     * @return $this
     */
    public function placeholder(string $placeholder): self
    {
        $this->inputProps['placeholder'] = $placeholder;
        return $this;
    }

    /**
     * Set the max items that can be selected (for multiple select)
     * 
     * @param int $maxItems
     * @return $this
     */
    public function maxItems(int $maxItems): self
    {
        $this->inputProps['maxItems'] = $maxItems;
        return $this;
    }
}