<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasFullWidth
{
    /** @var bool */
    protected bool $fullWidth = false;

    /**
     * @return $this
     * @throws \Exception
     * 
     */
    public function fullWidth(): static
    {
        $this->fullWidth = true;

        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     * 
     */
    public function isFullWidth(): bool
    {
        return $this->fullWidth;
    }
}