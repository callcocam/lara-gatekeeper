<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToName
{

    /**
     * The name.
     */
    protected string $name;

    /**
     * Define the name.
     * 
     * @param  string  $name
     * @return $this
     * 
     */
    public function name(string|Closure $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name.
     * 
     * @return string
     * 
     */
    public function getName()
    {
        return  $this->name;
    }
}
