<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasLayout
{
    /**
     * Set column span for grid layout
     * 
     * @param int $span 1-12
     * @return $this
     */
    public function colSpan(int $span): self
    {
        $this->colSpan = $span;
        return $this;
    }

    /**
     * Set full width (span all columns)
     * 
     * @param bool $full
     * @return $this
     */
    public function fullWidth(bool $full = true): self
    {
        $this->colSpan = $full ? 12 : null;
        return $this;
    }

    /**
     * Set half width (span half columns)
     * 
     * @param bool $half
     * @return $this
     */
    public function halfWidth(bool $half = true): self
    {
        $this->colSpan = $half ? 6 : null;
        return $this;
    }

    /**
     * Set field description/helper text
     * 
     * @param string $text
     * @return $this
     */
    public function description(string $text): self
    {
        $this->description = $text;
        return $this;
    }

    /**
     * Set CSS class for the field wrapper
     * 
     * @param string $class
     * @return $this
     */
    public function wrapperClass(string $class): self
    {
        $this->inputProps['wrapperClass'] = $class;
        return $this;
    }

    /**
     * Set CSS class for the input element
     * 
     * @param string $class
     * @return $this
     */
    public function inputClass(string $class): self
    {
        $this->inputProps['class'] = $class;
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
     * Set custom field width (CSS value)
     * 
     * @param string $width CSS width value (e.g. '200px', '50%')
     * @return $this
     */
    public function width(string $width): self
    {
        $this->inputProps['width'] = $width;
        return $this;
    }

    /**
     * Set field to be rendered in a specified section
     * 
     * @param string $section
     * @return $this
     */
    public function inSection(string $section): self
    {
        $this->inputProps['section'] = $section;
        return $this;
    }
}