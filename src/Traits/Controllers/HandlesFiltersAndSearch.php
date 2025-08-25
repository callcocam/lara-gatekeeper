<?php

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesFiltersAndSearch
{


    /**
     * Aplica filtros à query
     */
    protected function applyFilters(Builder &$query, Request $request): void
    {
        foreach ($this->filters() as $filter) {
            $name = $filter->getName();
            if ($request->filled($name)) {
                $value = $request->input($name);
                if ($filter->isFormatted()) {
                    $value = $filter->applyFormat($query, $value);
                } else {
                    if ($filter->isMultiple()) {
                        if (is_array($value)) {
                            $query->whereIn($name, $value);
                        } else {
                            $query->whereIn($name, explode(',', $value));
                        }
                    } else {
                        $query->where($name, $value);
                    }
                }
            }
        }
    }

    /**
     * Aplica busca à query
     */
    protected function applySearch(Builder &$query, Request $request): array
    {
        $searchableDbColumns = $this->getSearchableColumns();
        if ($request->filled('search')) {
            $search = $request->input('search');
            if (!empty($searchableDbColumns)) {
                $query->where(function ($q) use ($search, $searchableDbColumns) {
                    foreach ($searchableDbColumns as $dbColumn) {
                        // Tratamento para relacionamentos (ex: 'relation.field')
                        if (str_contains($dbColumn, '.')) {
                            [$relation, $relatedColumn] = explode('.', $dbColumn, 2);
                            $q->orWhereHas($relation, function ($relationQuery) use ($relatedColumn, $search) {
                                $relationQuery->where($relatedColumn, 'like', "%{$search}%");
                            });
                        } else {
                            $q->orWhere($dbColumn, 'like', "%{$search}%");
                        }
                    }
                });
            }
        }

        return $searchableDbColumns;
    }

    /**
     * Aplica filtros extras à query (método para ser sobrescrito)
     */
    protected function applyExtraFilters(Builder &$query, Request $request): void
    {
        // Implementação específica nos controllers filhos
    }

    /**
     * Define os relacionamentos que devem ser carregados (eager loaded)
     * na listagem principal (método index).
     */
    protected function getWithRelations(): array
    {
        return []; // Padrão: não carregar nenhum relacionamento
    }
}
