<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;

trait BelongsToConfig
{

    protected array $config = [];

    protected ?string $actionsType = 'inline';

    protected ?bool $selectable = false;

    protected ?bool $indeterminate = false;

    protected string | Closure | null $route = null;

    public function config(array $config): static
    {
        $this->config = $config;
        return $this;
    }


    public function actionsType(string $actionsType): static
    {
        $this->actionsType = $actionsType;
        return $this;
    }

    public function getActionsType(): string
    {
        return $this->actionsType;
    }


    public function selectable(bool $value = true): static
    {
        $this->selectable = $value;
        return $this;
    }

    public function getSelectable(): bool
    {
        if (count($this->getBulkActions()) > 0) {
            return true;
        }
        return $this->selectable;
    }

    public function indeterminate(bool $value = true): static
    {
        $this->indeterminate = $value;
        return $this;
    }

    public function getIndeterminate(): bool
    {
        return $this->indeterminate;
    }


    public function route(string | Closure $route): static
    {
        $this->route = $route;
        return $this;
    }

    public function getRoute(): string | Closure | null
    {
        return $this->route;
    }


    public function getConfig(): array
    {
        return array_merge(config('table.config', []), [
            'routeName' => $this->getRoute(),
            'actionsType' => $this->getActionsType(),
            'selectable' => $this->getSelectable(),
            'indeterminate' => $this->getIndeterminate(),
        ], $this->config);
    }
}
