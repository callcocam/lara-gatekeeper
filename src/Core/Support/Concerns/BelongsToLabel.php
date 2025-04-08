<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToLabel
{

    /**
     * The label.
     */
    protected ?string $label = null;

    /**
     * Define the label.
     * 
     * @param  string  $label
     * @return $this
     * 
     */
    public function label(string|Closure|null $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the label.
     * 
     * @return string
     * 
     */
    public function getLabel()
    {
        return  $this->label;
    }

    /**
     * Check if the label is set.
     * 
     * @return bool
     * 
     */
    public function hasLabel(): bool
    {
        return !is_null($this->label);
    }
    
}
