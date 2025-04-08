<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait para aplicar ordenação dinâmica em consultas Eloquent.
 */
trait WithSortRecords
{
    /**
     * Obtém a coluna configurada pelo nome.
     *
     * @param string $name
     * @return mixed
     */
    protected function getColumnByName(string $name)
    {
        return collect($this->getColumns())->first(function ($column) use ($name) {
            return $column->getName() === $name;
        });
    }

    /**
     * Aplica a ordenação na query com base nos parâmetros do request.
     *
     * @param Builder $query A query Eloquent.
     * @param \Illuminate\Http\Request $request O request contendo os parâmetros de ordenação.
     * @return Builder
     */
    public function applySortQuery(Builder &$query, $request)
    {
        // Verifica se o request possui o parâmetro `sortBy`
        if (!$request->has('sort') || !$request->has('direction')) {
            return $query;
        }


        // Define a direção da ordenação (padrão: ascendente)
        $direction = $request->get('direction', 'asc');

        // Obtém a coluna correspondente ao nome informado
        $column = $this->getColumnByName($request->get('sort'));

        // Se a coluna não foi encontrada, retorna a query sem modificações
        if (!$column) {
            return $query;
        }

        $field = $column->getName();

        // Verifica se é uma coluna relacionada ou utiliza `dot notation`
        if ($column->isRelation() || str($field)->contains('.')) {
            $query->orderBy(
                $column->getSortColumnForQuery($query, $field, explode('.', $field)),
                $direction
            );

            return $query;
        }

        // Ordena diretamente pela coluna na tabela principal
        $query->orderBy($field, $direction);

        return $query;
    }
}
