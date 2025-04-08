<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToDescription
{

    /**
     * The label.
     */
    protected ?string $description = null;
    /**
     * Define the label.
     * 
     * @param  string  $label
     * @return $this
     * 
     */
    public function description(string|Closure|null $description)
    {
        $this->description = $description;

        return $this;
    }
    /**
     * Get the label.
     * 
     * @return string
     * 
     */
    public function getDescription()
    {
        return  $this->description;
    }
    /**
     * Check if the label is set.
     * 
     * @return bool
     * 
     */
    public function hasDescription(): bool
    {
        return !is_null($this->description);
    }
}
