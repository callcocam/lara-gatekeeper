<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToOptions
{
    /**
     * The options for the filter.
     *
     * @var array
     */
    protected Closure|array $options = [];


    protected Closure|bool|null $multiple = false;

    /**
     * Set the options for the filter.
     *
     * @param array $options
     * @return static
     */
    public function options(Closure|array $options): static
    {
        $this->options = $options;
        return $this;
    }
    /**
     * Get the options for the filter.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->evaluate($this->options, [
            'request' => request()
        ]);
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;
        $this->component('SelectMultipleFilter');
        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->evaluate($this->multiple);
    }
}
