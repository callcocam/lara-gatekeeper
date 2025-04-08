<?php

/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasSearchQuery
{
    /**
     * Aplica a consulta de pesquisa no query builder.
     *
     * @param Builder $query
     * @param Request $request
     * @return void
     */
    protected function applySearchQuery(Builder &$query, Request $request): Builder
    {
        $search = $request->get('search');

        if (!blank($search)) {
            $searchableFields = $this->getSearchableFields();

            if (count($searchableFields) > 0) {
                $query->where(function (Builder $query) use ($search, $searchableFields) {
                    foreach ($searchableFields as $column) {
                        if ($this->isRelationshipColumn($column)) {
                            $this->applyRelationshipSearch($query, $column, $search);
                        } else {
                            $this->applyDirectSearch($query, $column, $search);
                        }
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Verifica se a coluna é de um relacionamento.
     *
     * @param mixed $column
     * @return bool
     */
    protected function isRelationshipColumn($column): bool
    {
        return str_contains($column->getName(), '.');
    }

    /**
     * Aplica busca para colunas relacionadas.
     *
     * @param Builder $query
     * @param mixed $column
     * @param string $search
     * @return void
     */
    protected function applyRelationshipSearch(Builder $query, $column, string $search): void
    {
        if (!$column->hasRelationshipName()) {
            $column->relationshipName(str($column->getName())->beforeLast('.'));
        }

        $columnName = str($column->getName())->afterLast('.');

        $query->orWhereHas($column->getRelationshipName(), function (Builder $query) use ($columnName, $search) {
            $query->where($columnName, 'like', "%{$search}%");
        });
    }

    /**
     * Aplica busca para colunas diretas.
     *
     * @param Builder $query
     * @param mixed $column
     * @param string $search
     * @return void
     */
    protected function applyDirectSearch(Builder $query, $column, string $search): void
    {
        $query->orWhere($column->getName(), 'like', "%{$search}%");
    }

    /**
     * Retorna os campos pesquisáveis.
     *
     * @return array
     */
    protected function getSearchableFields(): array
    {
        return collect($this->getColumns())
            ->filter(fn($field) => $field->isSearchable())
            ->all();
    }
}
