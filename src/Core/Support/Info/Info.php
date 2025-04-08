<?php

/**
 *  Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Info;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Info
{


    use Concerns\EvaluatesClosures;
    use Concerns\BelongsToRequest;
    use Concerns\BelongsToComponent;
    use Concerns\BelongsToConfig;
    use Concerns\HasBreadcrumbs;
    use Concerns\BelongsToOptions;
    use Concerns\HasFullWidth;
    use Concerns\BelonsToHeaderActions;

    use Traits\HasRecord;
    use Traits\HasGridLayout;
    use Traits\HasProps;

    /**
     * @var string
     */
    protected ?string $name = null;

    /**
     * @var array
     * 
     */
    protected array $sections = [];

    /**
     * @var string
     */
    protected string $action; 

    /**
     * @var string
     */
    protected ?string $model = null;

    /**
     * @var string
     */
    protected ?string $resource = null;


    public function __construct(Request $request)
    {
        $this->withRequest($request);
    }

    public static function make(Request $request): static
    {
        return (new static($request))
            ->route(Route::currentRouteName());
    }

    public function resource(string $resource): static
    {
        $this->resource = $resource;
        return $this;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function model(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function sections(array $sections): static
    {
        $this->sections = $sections;

        return $this;
    }

    public function getConfig(): array
    {
        return array_merge(config('table.config', []), [
            'routeName' => $this->getRoute(),   
        ], $this->config);
    }
    public function toArray(): array
    {
        return [
            'record' => $this->getRecord(),
            'actions' => $this->getHeaderActions(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'hasBreadcrumbs' => $this->hasBreadcrumbs(),
            'config' => array_merge($this->getConfig(), [
                'model' => $this->getModel(),  
                'fullWidth' => $this->isFullWidth(),
            ]),
            'actions' => [],
            'sections' => [],
        ];
    }
}
