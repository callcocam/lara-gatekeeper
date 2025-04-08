<?php

/**
 *  Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Table
{

    use Concerns\EvaluatesClosures;
    use Concerns\BelongsToRequest;
    use Concerns\BelongsToComponent;
    use Concerns\BelongsToOptions;
    use Concerns\HasFullWidth;
    use Concerns\BelonsToHeaderActions;
    use Concerns\HasBreadcrumbs;
    use Concerns\BelongsToConfig;


    use Traits\HasColumns;
    use Traits\HasActions;
    use Traits\HasFilters;
    use Traits\HasBulkActions;
    use Traits\HasSearchQuery;
    use Traits\WithSortRecords;
    use Traits\HasQuery;
    use Traits\HasExport;
    use Traits\HasImport;

    protected ?string $model = null;

    protected ?string $resource = null;

    public function __construct(Request $request)
    {
        $this->withRequest($request);
    }

    public static function make(Request $request)
    {
        return new static($request);
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

    protected function getData()
    {
        $query = $this->getQuery();

        $request = $this->getRequest();
        // Aplica ordenação e filtros
        $this->applySearchQuery($query, $request);
        $this->applyFilters($query, $request);
        $this->applySortQuery($query, $request);
        // Obtém os resultados paginados
        $rows = $query
            ->withCasts($this->getCasts())
            ->paginate($request->get('perPage', 10))
            ->onEachSide(3)
            ->withPath(Route::current()->uri());

        if ($this->resource) {
            return $this->resource::collection($rows->through(fn($row) =>  $this->resolveRow($row)));
        }

        return $rows->toArray();
    }

    /**
     * Resolve os valores das colunas para uma linha
     */
    protected function resolveRow($row): array
    {
        $data = collect($this->getColumns())
            ->mapWithKeys(function ($column) use ($row) {
                $value = data_get($row, $column->getName());

                if ($formatCallback = $column->getFormatCallback()) {
                    $value = $this->evaluate($formatCallback, [
                        'value' => $value,
                        'model' => $row
                    ]);
                }

                return [$column->getName() => $value];
            })
            ->toArray();

        return array_merge($data, [
            'id' => $row->id,
            'actions' => $this->resolveRowActions($row),
        ]);
    }

    /**
     * Resolve as ações disponíveis para uma linha
     */
    protected function resolveRowActions($row): array
    {
        return collect($this->getActions())
            ->map(function ($action) use ($row) {
                if ($url = $action->getUrl()) {
                    $action->url($this->evaluate($url, ['model' => $row]));
                }
                return $action->toArray();
            })
            ->toArray();
    }

    /**
     * Obtém os casts das colunas
     */
    protected function getCasts(): array
    {
        return collect($this->getColumns())
            ->filter->isCast()
            ->mapWithKeys(fn($column) => [
                $column->getName() => $column->getFormatState()
            ])
            ->toArray();
    }

    public function toArray()
    {
         
        return [
            'data' => $this->getData(),
            'columns' => collect($this->getColumns())->map->toArray()->values()->toArray(),
            'filters' => collect($this->getFilters())->map->toArray()->values()->toArray(),
            'bulkActions' => collect($this->getBulkActions())->map->toArray()->values()->toArray(),
            'actions' => $this->getHeaderActions(),
            'breadcrumbs' => $this->getBreadcrumbs(),
            'hasBreadcrumbs' => $this->hasBreadcrumbs(),
            'config' => array_merge($this->getConfig(), [
                'model' => $this->getModel(),
                 'export'=>$this->getExport(),
                 'import'=>$this->getImport(),
            ])
        ];
    }
}
