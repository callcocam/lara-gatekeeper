<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasRadio
{
    /**
     * Convert field to radio group
     * 
     * @return $this
     */
    public function radioGroup(): self
    {
        $this->type = 'radio-group';
        return $this;
    }

    /**
     * Set default selected option
     * 
     * @param mixed $value
     * @return $this
     */
    public function defaultValue(mixed $value): self
    {
        $this->inputProps['defaultValue'] = $value;
        return $this;
    }

    /**
     * Set radio layout to inline
     * 
     * @param bool $inline
     * @return $this
     */
    public function radioInline(bool $inline = true): self
    {
        $this->inputProps['inline'] = $inline;
        return $this;
    }

    /**
     * Set number of columns for radio group layout
     * 
     * @param int $cols
     * @return $this
     */
    public function radioGridCols(int $cols): self
    {
        $this->gridCols = $cols;
        return $this;
    }
    
    /**
     * Set radio buttons to card style
     * 
     * @param bool $cards
     * @return $this
     */
    public function cards(bool $cards = true): self
    {
        if ($cards) {
            $this->type = 'radio-cards';
        }
        return $this;
    }
    
    /**
     * Set radio label position
     * 
     * @param string $position 'left' or 'right'
     * @return $this
     */
    public function radioLabelPosition(string $position): self
    {
        $this->inputProps['labelPosition'] = $position;
        return $this;
    }
}