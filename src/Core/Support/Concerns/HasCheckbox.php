<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasCheckbox
{
    /**
     * Set checkbox to checked
     * 
     * @param bool $checked
     * @return $this
     */
    public function checked(bool $checked = true): self
    {
        $this->inputProps['checked'] = $checked;
        return $this;
    }

    /**
     * Set checkbox to toggle mode
     * 
     * @param bool $toggle
     * @return $this
     */
    public function toggle(bool $toggle = true): self
    {
        if ($toggle) {
            $this->type = 'toggle';
        } else {
            $this->type = 'checkbox';
        }
        return $this;
    }

    /**
     * Set checkbox group layout with multiple options
     * 
     * @param array $options
     * @return $this
     */
    public function checkboxGroup(array $options): self
    {
        $this->type = 'checkbox-group';
        $this->options = $options;
        return $this;
    }

    /**
     * Set number of columns for checkbox group layout
     * 
     * @param int $cols
     * @return $this
     */
    public function checkboxGridCols(int $cols): self
    {
        $this->gridCols = $cols;
        return $this;
    }

    /**
     * Set checkbox layout to inline
     * 
     * @param bool $inline
     * @return $this
     */
    public function checkboxInline(bool $inline = true): self
    {
        $this->inputProps['inline'] = $inline;
        return $this;
    }
    
    /**
     * Set checkbox label position
     * 
     * @param string $position 'left' or 'right'
     * @return $this
     */
    public function checkboxLabelPosition(string $position): self
    {
        $this->inputProps['labelPosition'] = $position;
        return $this;
    }
}