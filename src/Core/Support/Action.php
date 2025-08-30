<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support;

use Callcocam\LaraGatekeeper\Core;
use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Str;

class Action
{
    use Core\Concerns\FactoryPattern;
    use Table\Concerns\HasAction;
    use Core\Concerns\EvaluatesClosures;
    use Core\Concerns\BelongsToId;
    use Core\Concerns\BelongsToName;
    use Core\Concerns\BelongsToLabel;
    use Core\Concerns\BelongsToIcon;
    use Core\Concerns\BelongsToColor;
    use Core\Concerns\BelongsToVisible;
    use Core\Concerns\BelongsToPermission;
    use Core\Concerns\BelongsToType;

    protected string $component = 'Link';
    public readonly string $accessorKey;
    public ?string $variant = null;
    protected ?string $method = 'GET';
    public ?string $routeSuffix = null;
    public ?string $routeNameBase = null;
    public ?string $permission = null;
    public ?string $fullRouteName = null;
    public array $routeParameters = []; 
    public Closure|string|null $urlCallback = null;
    public ?Closure $confirmCallback = null;
    public ?Closure $callback = null;

    // Cache para rotas e parâmetros
    private static array $routeCache = [];
    private static array $parameterCache = [];
    public ?int $order = null;
    public ?string $position = 'row';

    protected function __construct(string $accessorKey, ?string $header = null)
    {
        $this->accessorKey = $accessorKey;
        $label = $header ?? Str::title($accessorKey);
        
        $this->label($label)
             ->id($this->accessorKey)
             ->name($this->accessorKey);
    }

    public function order(int $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function component(string $component): static
    {
        $this->component = $component;
        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method ?? 'GET';
    }

    public function confirm(Closure $callback): static
    {
        $this->component = 'ConfirmModal';
        $this->confirmCallback = $callback;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function routeSuffix(string $routeSuffix): static
    {
        $this->routeSuffix = $routeSuffix;
        return $this;
    }

    public function routeNameBase(string $routeNameBase): static
    {
        $this->routeNameBase = $routeNameBase;
        return $this;
    }

    public function permission(string $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function fullRouteName(string $fullRouteName): static
    {
        $this->fullRouteName = $fullRouteName;
        return $this;
    }

    public function routeParameters(array $parameters): static
    {
        $this->routeParameters = $parameters;
        return $this;
    }

    public function url(Closure|string $callback): static
    {
        $this->urlCallback = $callback;
        return $this;
    }

    public function action(Closure $callback): static
    {
        $this->callback = $callback;
        return $this;
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * Gera nome completo da rota com cache
     */
    protected function resolveRouteName(): ?string
    {
        if ($this->fullRouteName) {
            return $this->fullRouteName;
        }

        if (!$this->routeNameBase || !$this->routeSuffix) {
            return null;
        }

        $cacheKey = "route_name_{$this->routeNameBase}_{$this->routeSuffix}";
        
        return self::$routeCache[$cacheKey] ??= "{$this->routeNameBase}.{$this->routeSuffix}";
    }

    /**
     * Verifica permissão do usuário com cache otimizado
     */
    public function hasPermission($user = null): bool
    {
        if (!$this->permission) {
            return true;
        }

        $user ??= auth()->user();
        
        if (!$user) {
            return false;
        }

        // Cache da verificação de permissão por usuário
        $cacheKey = "permission_{$user->id}_{$this->permission}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return Gate::forUser($user)->check($this->permission);
        });
    }

    /**
     * Resolve parâmetros da rota automaticamente com cache
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

        $cacheKey = "route_params_{$routeName}";
        
        if (!isset(self::$parameterCache[$cacheKey])) {
            try {
                $route = Route::getRoutes()->getByName($routeName);
                self::$parameterCache[$cacheKey] = $route?->parameterNames() ?? [];
            } catch (\Exception) {
                self::$parameterCache[$cacheKey] = [];
            }
        }

        return $this->extractParametersFromItem(self::$parameterCache[$cacheKey], $item);
    }

    /**
     * Extrai parâmetros do item baseado nos nomes dos parâmetros da rota
     */
    protected function extractParametersFromItem(array $parameterNames, $item): array
    {
        $parameters = [];
        
        foreach ($parameterNames as $paramName) {
            $value = $this->resolveParameterValue($paramName, $item);
            if ($value !== null) {
                $parameters[$paramName] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve valor do parâmetro usando estratégias múltiplas otimizadas
     */
    protected function resolveParameterValue(string $paramName, $item)
    {
        // Estratégias ordenadas por probabilidade de sucesso
        $strategies = [
            $paramName,
            'id',
            "{$paramName}_id",
            Str::singular($paramName),
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
     * Gera URL da action com tratamento de erro melhorado
     */
    public function generateUrl($item = null): ?string
    {
        if ($this->urlCallback) {
            $result = $this->evaluate($this->urlCallback, ['record' => $item]);
            return is_string($result) ? $result : (string) $result;
        }

        $routeName = $this->resolveRouteName();
        if (!$routeName) {
            return null;
        }

        try {
            $parameters = $this->resolveRouteParameters($item);
            return route($routeName, $parameters);
        } catch (\Exception $e) {
            // Log do erro para debug se necessário
            logger("Erro ao gerar URL para rota {$routeName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Renderiza os dados da action otimizado
     */
    public function render($item = null): array
    {
        $data = [
            'id' => $this->getId(),
            'accessorKey' => $this->accessorKey,
            'component' => $this->component,
            'label' => $this->getLabel(),
            'method' => $this->getMethod(),
            'icon' => $this->getIcon(),
            'visible' => $this->isVisible($item),
            'order' => $this->getOrder(),
            'position' => $this->getPosition(),
        ];

        // Adiciona campos opcionais apenas se definidos
        if ($this->variant) {
            $data['variant'] = $this->variant;
        }

        if ($iconPosition = $this->getIconPosition()) {
            $data['iconPosition'] = $iconPosition;
        }

        if ($size = $this->getSize()) {
            $data['size'] = $size;
        }

        if ($url = $this->generateUrl($item)) {
            $data['url'] = $url;
        }

        // Campos de visibilidade condicional
        $visibilityFields = [ 
            'visibleWhenIndex' => $this->getVisibleWhenIndex(),
            'visibleWhenCreate' => $this->getVisibleWhenCreate(),
            'visibleWhenShow' => $this->getVisibleWhenShow(),
            'visibleWhenEdit' => $this->getVisibleWhenEdit(),
            'visibleWhenDelete' => $this->getVisibleWhenDelete(),
        ];

        foreach ($visibilityFields as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        // Confirmação
        if ($this->confirmCallback) {
            $data['confirm'] = $this->evaluate($this->confirmCallback, ['record' => $item]);
        }

        return $data;
    }

    public function toArray($item = null): array
    {
        return $this->render($item);
    }

    // Métodos de conveniência otimizados
    public static function view(string $routeBase): static
    {
        return static::make('view', 'Visualizar')
            ->routeNameBase($routeBase)
            ->routeSuffix('show')
            ->variant('outline')
            ->icon('eye');
    }

    public static function edit(string $routeBase): static
    {
        return static::make('edit', 'Editar')
            ->routeNameBase($routeBase)
            ->routeSuffix('edit')
            ->variant('default')
            ->icon('pencil');
    }

    public static function delete(string $routeBase, ?string $nameField = 'name'): static
    {
        return static::make('delete', 'Excluir')
            ->routeNameBase($routeBase)
            ->routeSuffix('destroy')
            ->variant('destructive')
            ->icon('Trash')
            ->method('DELETE')
            ->confirm(fn($record) => [
                'title' => 'Confirmar Exclusão',
                'description' => sprintf(
                    'Tem certeza que deseja excluir "%s"? Esta ação não pode ser desfeita.',
                    data_get($record, $nameField, 'este item')
                ),
                'confirmButtonText' => 'Sim, excluir',
                'cancelButtonText' => 'Cancelar',
            ]);
    }

    /**
     * Limpa o cache estático (útil para testes)
     */
    public static function clearCache(): void
    {
        self::$routeCache = [];
        self::$parameterCache = [];
    }

    /**
     * Método para criar actions customizadas rapidamente
     */
    public static function custom(
        string $key,
        string $label,
        string $route,
        string $method = 'GET',
        ?string $variant = null
    ): static {
        return static::make($key, $label)
            ->fullRouteName($route)
            ->method($method)
            ->when($variant, fn($action) => $action->variant($variant));
    }
}