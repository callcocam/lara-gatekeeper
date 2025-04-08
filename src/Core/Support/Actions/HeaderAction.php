<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Actions;

use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToIcon;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToLabel;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\EvaluatesClosures;

class HeaderAction
{
    use EvaluatesClosures, BelongsToIcon, BelongsToLabel;

    protected ?string $route = null;
    protected array $routeParams = [];
    protected ?string $href = null;
    protected string $method = 'GET';
    protected string $variant = 'default';
    protected $action = null;
    protected $visible = true;

    public static function make(string $label): self
    {
        return new static($label);
    }

    public function __construct(string $label)
    {
        $this->label = __($label);
    }

    public function route(string $route, array $params = []): self
    {
        $this->route = $route;
        $this->routeParams = $params;
        return $this;
    }

    public function href(string $url): self
    {
        $this->href = $url;
        return $this;
    }

    public function method(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function action(callable $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function variant(string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }

    public function visible(callable|bool $condition): self
    {
        $this->visible = $condition;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'iconSize' => $this->getIconSize(),
            'route' => $this->route,
            'routeParams' => $this->routeParams,
            'href' => $this->href,
            'method' => $this->method,
            'variant' => $this->variant,
            'action' => $this->action,
        ];
    }

    public function isVisible(): bool
    {
        return $this->evaluate($this->visible);
    }
}
