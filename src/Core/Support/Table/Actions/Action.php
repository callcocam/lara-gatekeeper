<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Actions;

use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToIcon;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToLabel;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToName; 
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToVariant;
use Callcocam\LaraGatekeeper\Core\Support\Concerns\BelongsToUrl;
use Closure;

class Action
{
    use BelongsToName;
    use BelongsToLabel;
    use BelongsToIcon;
    use BelongsToVariant;
    use BelongsToUrl;

    protected string | Closure | null $route = null;

    protected string | Closure | array | null $routeParams = null;

    public function __construct(?string $name = null, ?string $label = null)
    {
        $this->name = $name;
        $this->label = $label ?? str($name)->title()->replace('_', ' ')->toString();
    }

    public static function make(?string $name = null, ?string $label = null): static
    {
        return new static($name, $label);
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

    public function routeParams(string | Closure | array $routeParams): static
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    public function getRouteParams(): string | Closure | array | null
    {
        return $this->routeParams;
    }

    public function toArray(): array        
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'icon' => $this->getIcon(),
            'variant' => $this->getVariant(),
            'url' => $this->getUrl(),
            'route' => $this->getRoute(),
            'routeParams' => $this->getRouteParams(),
        ];
    }
}
