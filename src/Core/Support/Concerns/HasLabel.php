<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasLabel
{
    /**
     * Set field label
     * 
     * @param string $label
     * @return $this
     */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Hide field label
     * 
     * @param bool $hide
     * @return $this
     */
    public function hideLabel(bool $hide = true): self
    {
        $this->inputProps['hideLabel'] = $hide;
        return $this;
    }

    /**
     * Set CSS class for the label
     * 
     * @param string $class
     * @return $this
     */
    public function labelClass(string $class): self
    {
        $this->inputProps['labelClass'] = $class;
        return $this;
    }

    /**
     * Set field label position
     * 
     * @param string $position 'top', 'left', 'right', 'bottom'
     * @return $this
     */
    public function labelPosition(string $position): self
    {
        $this->inputProps['labelPosition'] = $position;
        return $this;
    }

    /**
     * Add a tooltip to the label
     * 
     * @param string $tooltip
     * @return $this
     */
    public function tooltip(string $tooltip): self
    {
        $this->inputProps['tooltip'] = $tooltip;
        return $this;
    }

    /**
     * Add a badge to the label
     * 
     * @param string $text Text to display in badge
     * @param string $type Badge type (default, info, success, warning, danger)
     * @return $this
     */
    public function badge(string $text, string $type = 'default'): self
    {
        $this->inputProps['badge'] = [
            'text' => $text,
            'type' => $type,
        ];
        return $this;
    }

    /**
     * Make the label clickable
     * 
     * @param bool $clickable
     * @return $this
     */
    public function clickableLabel(bool $clickable = true): self
    {
        $this->inputProps['clickableLabel'] = $clickable;
        return $this;
    }

    /**
     * Add a helper icon next to the label
     * 
     * @param string $icon Icon name
     * @param string $tooltip Tooltip text for the icon
     * @return $this
     */
    public function helpIcon(string $icon, string $tooltip): self
    {
        $this->inputProps['helpIcon'] = [
            'icon' => $icon,
            'tooltip' => $tooltip,
        ];
        return $this;
    }
}