<?php

/**
 *  Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Form
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
     * @example POST | GET | PUT | DELETE
     */
    protected string $method = 'POST';

    /**
     * @var string
     */
    protected ?string $model = null;

    /**
     * @var array
     */
    protected array $values = [];

    /**
     * @var string
     */
    protected ?string $resource = null;

    public function __construct(Request $request)
    {
        $this->withRequest($request);
        $this->layout(2);
        $this->grid(2);
    }

    public static function make(Request $request)
    {
        return new static($request);
    }

    public function method(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
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

    protected function section(Sections $section): static
    {

        $this->sections[] = $section;
        return $this;
    }

    public function sections(array  $sections): static
    {
        foreach ($sections as $section) {
            $this->section($section);
        }
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }


    protected function getSections(): array
    {
        return array_map(function (Sections $section) {
            $fields = $section->toArray($this->record); 
            foreach ($fields['fields'] as $field) { 
                $fieldName = $field['name'];
                $recordValue = data_get($this->record, $fieldName);
                $defaultValue = $field['default'] ?? null;
                
                // Use record value if it exists, otherwise use default value
                $this->values[$fieldName] = $recordValue !== null ? $recordValue : $defaultValue;
            }
            return $fields;
        }, $this->sections);
    }


    public function getConfig(): array
    {
        return array_merge(config('table.config', []), [
            'routeName' => $this->getRoute(),
            'grid' => $this->getGrid(),
            'layout' => $this->getLayout(),
        ], $this->config);
    }

    public function getAction(): string
    {
        if (Route::has($this->getRoute())) {
            return route($this->getRoute(), $this->getRecord());
        }
        return $this->getRoute();
    }

    public function getValues()
    {
        
        return $this->values;
    }

    public function toArray()
    {
        return [
            'sections' => $this->getSections(),
            'record' => $this->getRecord(),
            'values' => $this->getValues(),
            'actions' => $this->getHeaderActions(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'hasBreadcrumbs' => $this->hasBreadcrumbs(),
            'config' => array_merge($this->getConfig(), [
                'model' => $this->getModel(),
                'method' => $this->getMethod(),
                'action' => $this->getAction(),
            ])
        ];
    }
}
