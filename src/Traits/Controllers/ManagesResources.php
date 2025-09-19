<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait ManagesResources
{
    protected ?string $model = null;
    protected string $resourceName = '';
    protected string $pluralResourceName = '';
    protected string $routeNameBase = '';
    protected array $defaultBreadcrumbs = [];
    protected string $pageTitle = '';

    /**
     * Inicializa os nomes dos recursos baseado no modelo
     */
    protected function initializeResourceNames(): void
    {
        if ($this->model) {
            $baseName = class_basename($this->model);
            if ($this->resourceName == '') {
                $this->resourceName = Str::snake($baseName);
            }
            if ($this->pluralResourceName == '') {
                $this->pluralResourceName = Str::plural($this->resourceName);
            }
            if ($this->routeNameBase == '') {
                $this->routeNameBase = 'admin.' . str($baseName)->snake()->plural()->toString();
            }
        } else {
            throw new \Exception('A propriedade $model deve ser definida no controller filho.');
        }
    }

    protected function getViewPrefix(): string
    {
        return  'gatekeeper/crud';
    }

    /**
     * Retorna o nome do recurso traduzido
     */
    protected function getResourceName(): string
    {
        return __($this->resourceName);
    }

    /**
     * Retorna o nome plural do recurso traduzido
     */
    protected function getPluralResourceName(): string
    {
        return __($this->pluralResourceName);
    }

    /**
     * Retorna a base do nome das rotas
     */
    protected function getRouteNameBase(): string
    {
        return __($this->routeNameBase);
    }

    /**
     * Gera o título da página baseado na ação
     */
    protected function generatePageTitle(string $action, ?Model $modelInstance = null): string
    {
        $resourceTitle = Str::ucfirst(str_replace('_', ' ', $this->getPluralResourceName()));
        $title = null;

        switch ($action) {
            case 'index':
                $title = sprintf('Gerenciar %s', $resourceTitle);
                break;
            case 'create':
                $title = sprintf('Cadastrar %s', Str::singular($resourceTitle));
                break;
            case 'edit':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $title = sprintf('Editar %s', Str::singular($resourceTitle)) . ($identifier ? ": {$identifier}" : '');
                break;
            case 'show':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $title = sprintf('Detalhes de %s', Str::singular($resourceTitle)) . ($identifier ? ": {$identifier}" : '');
                break;
            default:
                $title = $resourceTitle;
        }

        return __($title);
    }

    /**
     * Gera a descrição da página baseado na ação
     */
    protected function generatePageDescription(string $action, ?Model $modelInstance = null): string
    {
        return '';
    }

    /**
     * Gera os breadcrumbs padrão baseado na ação
     */
    protected function generateDefaultBreadcrumbs(string $action, ?Model $modelInstance = null): array
    {
        $pluralTitle = $this->getPluralResourceName();
        $singularTitle = Str::singular($pluralTitle);
        $indexRoute = route($this->getRouteNameBase() . '.index');

        $breadcrumbs = [
            ['title' => 'Dashboard', 'href' => route('dashboard')],
            ['title' => $pluralTitle, 'href' => $indexRoute],
        ];

        switch ($action) {
            case 'create':
                $breadcrumbs[] = ['title' => "Cadastrar Novo {$singularTitle}", 'href' => ''];
                break;
            case 'edit':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $breadcrumbs[] = ['title' => "Editar {$singularTitle}" . ($identifier ? ": {$identifier}" : ''), 'href' => ''];
                break;
            case 'show':
                $identifier = $modelInstance ? ($modelInstance->name ?? $modelInstance->id) : '';
                $breadcrumbs[] = ['title' => "Detalhes" . ($identifier ? ": {$identifier}" : ''), 'href' => ''];
                break;
        }

        return $breadcrumbs;
    }

    /**
     * Retorna os middlewares das rotas
     */
    protected function getRouteMiddleware(): array
    {
        return ['auth'];
    }

    /**
     * Retorna as views específicas para cada ação
     */
    protected function getViewIndex(): string
    {
        return "{$this->getViewPrefix()}/Index";
    }

    protected function getViewCreate(): string
    {
        return "{$this->getViewPrefix()}/Create";
    }

    protected function getViewShow(): string
    {
        return "{$this->getViewPrefix()}/Show";
    }

    protected function getViewEdit(): string
    {
        return "{$this->getViewPrefix()}/Edit";
    }

    protected function getParamName($modelInstance = null): string
    {
        if ($modelInstance) {
            $paramName = Str::snake(class_basename($modelInstance));
            return $paramName;
        }
        return 'model';
    }

    protected function getToArrayManagesResources($name = 'index', ?Model $modelInstance = null): array
    {
        $paramName = $this->getParamName($modelInstance);
        $params[$paramName] = $modelInstance;
        return [
            'pageTitle' => $this->generatePageTitle($name, $modelInstance),
            'pageDescription' => $this->generatePageDescription($name, $modelInstance),
            'breadcrumbs' => $this->generateDefaultBreadcrumbs($name, $modelInstance),
            'routeNameBase' => $this->getRouteNameBase(),
            'useCustomFilters' => method_exists($this, 'useCustomFilters') ? $this->useCustomFilters() : false,
            'resourceName' => $this->getResourceName(),
            'routes' => [
                'index' => $this->getBaseRouteIndex(),
                'create' => $this->getBaseRouteCreate(),
                'store' => $this->getBaseRouteStore(),
                'show' => $this->getBaseRouteShow($params),
                'edit' => $this->getBaseRouteEdit($params),
                'update' => $this->getBaseRouteUpdate($params),
                'destroy' => $this->getBaseRouteDestroy($params),
            ],
        ];
    }

    protected function getBaseRouteIndex(): string
    {
        if (Route::has($this->getRouteNameBase() . '.index'))
            return route($this->getRouteNameBase() . '.index');
        return '#';
    }

    protected function getBaseRouteCreate(): string
    {
        if (Route::has($this->getRouteNameBase() . '.create'))
            return route($this->getRouteNameBase() . '.create');
        return '#';
    }

    protected function getBaseRouteStore(): string
    {
        if (Route::has($this->getRouteNameBase() . '.store'))
            return route($this->getRouteNameBase() . '.store');
        return '#';
    }

    protected function getBaseRouteShow($params = []): string
    {
        if (array_filter($params) && Route::has($this->getRouteNameBase() . '.show'))
            return route($this->getRouteNameBase() . '.show', $params);
        return '#';
    }

    protected function getBaseRouteEdit($params = []): string
    {
        if (array_filter($params) && Route::has($this->getRouteNameBase() . '.edit'))
            return route($this->getRouteNameBase() . '.edit', $params);
        return '#';
    }

    protected function getBaseRouteUpdate($params = []): string
    {
        if (array_filter($params) && Route::has($this->getRouteNameBase() . '.update'))
            return route($this->getRouteNameBase() . '.update', $params);
        return '#';
    }

    protected function getBaseRouteDestroy($params = []): string
    {
        if (array_filter($params) && Route::has($this->getRouteNameBase() . '.destroy'))
            return route($this->getRouteNameBase() . '.destroy', $params);
        return '#';
    }
}
