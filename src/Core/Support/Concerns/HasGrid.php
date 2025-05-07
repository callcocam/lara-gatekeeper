<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasGrid
{
    /**
     * Set number of columns for grid layout
     * 
     * @param int $cols
     * @return $this
     */
    public function gridCols(int $cols): self
    {
        $this->gridCols = $cols;
        return $this;
    }

    /**
     * Set row span for grid layout
     * 
     * @param int $span
     * @return $this
     */
    public function rowSpan(int $span): self
    {
        $this->inputProps['rowSpan'] = $span;
        return $this;
    }

    /**
     * Set field to take full width row
     * 
     * @param bool $fullRow
     * @return $this
     */
    public function fullRow(bool $fullRow = true): self
    {
        $this->inputProps['fullRow'] = $fullRow;
        return $this;
    }

    /**
     * Set field order in grid layout
     * 
     * @param int $order
     * @return $this
     */
    public function order(int $order): self
    {
        $this->inputProps['order'] = $order;
        return $this;
    }

    /**
     * Set field alignment in grid cell
     * 
     * @param string $alignment 'start', 'center', 'end', 'stretch'
     * @return $this
     */
    public function alignment(string $alignment): self
    {
        $this->inputProps['alignment'] = $alignment;
        return $this;
    }
}