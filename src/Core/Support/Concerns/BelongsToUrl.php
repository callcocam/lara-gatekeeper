<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToUrl
{
    protected string | Closure | null $url = null;

    public function url(string | Closure $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string | Closure | null
    {
        return $this->url;
    }
} 