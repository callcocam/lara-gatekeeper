<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Callcocam\LaraGatekeeper\Core\Support\Table\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    /** @var Filter[] */
    protected array $filters = [];

    public function addFilter(Filter $filter): static
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function filters(array $filters): static
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Aplica a ordenação na query com base nos parâmetros do request.
     *
     * @param Builder $query A query Eloquent.
     * @param \Illuminate\Http\Request $request O request contendo os parâmetros de ordenação.
     * @return Builder
     */
    public function applyFilters(Builder &$query, $request)
    {
        foreach ($this->getFilters() as $filter) {
            $value = $request->get($filter->getName());
            if ($value) {
                $filter->apply($query, $value);
            }
        }
    }
}
