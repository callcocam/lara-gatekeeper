<?php

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Route as RoutingRoute;

class Action
{
    use Table\Concerns\HasAction;
    use Core\Concerns\EvaluatesClosures;
    use Core\Concerns\BelongsToId;
    use Core\Concerns\BelongsToName;
    use Core\Concerns\BelongsToLabel;
    use Core\Concerns\BelongsToIcon;
    use Core\Concerns\BelongsToColor;

    protected string $component = 'Link';
    public string $accessorKey;
    public ?string $variant = null;
    public ?string $routeSuffix = null;
    public ?string $routeNameBase = null;
    public ?string $permission = null;
    public ?string $fullRouteName = null;
    public array $routeParameters = [];
    public ?Closure $visibilityCallback = null;
    public ?Closure $urlCallback = null;
    public ?Closure $confirmCallback = null;


    protected function __construct(string $accessorKey, ?string $header = null)
    {
        $this->label($header ?? str($accessorKey)->title()->toString());
        $this->accessorKey = $accessorKey;
        $this->id($this->accessorKey);
        $this->name($this->accessorKey);
    }

    public static function make(string $accessorKey, ?string $header = null): self
    {
        return new static($accessorKey, $header);
    }

    public function component(string $component): self
    {
        $this->component = $component;
        return $this;
    }

    public function confirm(Closure $callback): self
    {
        $this->confirmCallback = $callback;
        return $this;
    }

    public function variant(?string $variant): self
    {
        $this->variant = $variant;
        return $this;
    }

    public function routeSuffix(?string $routeSuffix): self
    {
        $this->routeSuffix = $routeSuffix;
        return $this;
    }

    public function routeNameBase(?string $routeNameBase): self
    {
        $this->routeNameBase = $routeNameBase;
        return $this;
    }

    public function permission(?string $permission): self
    {
        $this->permission = $permission;
        return $this;
    }

    public function fullRouteName(string $fullRouteName): self
    {
        $this->fullRouteName = $fullRouteName;
        return $this;
    }

    public function routeParameters(array $parameters): self
    {
        $this->routeParameters = $parameters;
        return $this;
    }

    public function visibleWhen(Closure $callback): self
    {
        $this->visibilityCallback = $callback;
        return $this;
    }

    public function url(Closure $callback): self
    {
        $this->urlCallback = $callback;
        return $this;
    }

    /**
     * Gera nome completo da rota
     */
    protected function resolveRouteName(): ?string
    {
        if ($this->fullRouteName) {
            return $this->fullRouteName;
        }

        if ($this->routeNameBase && $this->routeSuffix) {
            return $this->routeNameBase . '.' . $this->routeSuffix;
        }

        return null;
    }

    /**
     * Verifica permissão do usuário
     */
    public function hasPermission($user = null): bool
    {
        if (!$this->permission) {
            return true;
        }

        $user = $user ?? auth()->user();
        return $user ? Gate::forUser($user)->check($this->permission) : false;
    }

    /**
     * Verifica visibilidade da action
     */
    public function isVisible($item = null): mixed
    {
        if (!$this->hasPermission()) {
            return false;
        }

        if ($this->visibilityCallback) {
            return $this->evaluate($this->visibilityCallback, ['item' => $item]);
        }

        return true;
    }

    /**
     * Resolve parâmetros da rota automaticamente
     */
    protected function resolveRouteParameters($item): array
    {
        if (!empty($this->routeParameters)) {
            return $this->routeParameters;
        }

        $routeName = $this->resolveRouteName();
        if (!$routeName || !$item) {
            return [];
        }

        try {
            $route = Route::getRoutes()->getByName($routeName);
            if (!$route) {
                return [];
            }

            return $this->extractParametersFromRoute($route, $item);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extrai parâmetros da rota baseado no item
     */
    protected function extractParametersFromRoute(RoutingRoute $route, $item): array
    {
        $parameters = [];
        $parameterNames = $route->parameterNames();

        foreach ($parameterNames as $paramName) {
            $value = $this->resolveParameterValue($paramName, $item);
            if ($value !== null) {
                $parameters[$paramName] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve valor do parâmetro usando estratégias múltiplas
     */
    protected function resolveParameterValue(string $paramName, $item)
    {
        $strategies = [
            $paramName,
            $paramName . '_id',
            'id',
            str($paramName)->singular()->toString(),
        ];

        foreach ($strategies as $key) {
            $value = data_get($item, $key);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Gera URL da action
     */
    public function generateUrl($item = null): ?string
    {
        if ($this->urlCallback) {
            return str($this->evaluate($this->urlCallback, ['record' => $item]))->toString();
        }

        $routeName = $this->resolveRouteName();
        if (!$routeName) {
            return null;
        }

        try {
            $parameters = $this->resolveRouteParameters($item);
            return route($routeName, $parameters);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toArray($item = null): array
    {
        return $this->render($item);
    }

    public function render($item = null): array
    {
        return [
            'id' => $this->getId(),
            'accessorKey' => $this->accessorKey,
            'component' => $this->component,
            'label' => $this->getLabel(),
            'variant' => $this->variant,
            'icon' => $this->getIcon(),
            'iconPosition' => $this->getIconPosition(),
            'url' => $this->generateUrl($item),
            'visible' => $this->isVisible($item),
            'confirm' => $this->confirmCallback ? $this->evaluate($this->confirmCallback, ['record' => $item]) : null
        ];
    }

    // Métodos de conveniência
    public static function view(string $routeBase): self
    {
        return static::make('view', 'Visualizar')
            ->routeNameBase($routeBase)
            ->routeSuffix('show')
            ->variant('outline')
            ->icon('eye');
    }

    public static function edit(string $routeBase): self
    {
        return static::make('edit', 'Editar')
            ->routeNameBase($routeBase)
            ->routeSuffix('edit')
            ->variant('default')
            ->icon('pencil');
    }

    public static function delete(string $routeBase): self
    {
        return static::make('delete', 'Excluir')
            ->routeNameBase($routeBase)
            ->routeSuffix('destroy')
            ->variant('destructive')
            ->icon('trash-2');
    }
}
