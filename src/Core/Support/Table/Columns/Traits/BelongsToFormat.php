<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

use Closure;

trait BelongsToFormat
{
    protected ?Closure $formatCallback = null;

    public function format(Closure $callback): static
    {
        $this->formatCallback = $callback;
        return $this;
    }

    public function formatValue($value): mixed
    {
        if ($this->formatCallback) {
            return call_user_func($this->formatCallback, $value);
        }
        return $value;
    }   

    public function getFormatCallback(): ?Closure
    {
        return $this->formatCallback;
    }
} 
