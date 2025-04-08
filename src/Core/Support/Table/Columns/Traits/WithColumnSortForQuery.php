<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

use Illuminate\Database\Eloquent\Builder;

trait WithColumnSortForQuery
{
    public function getSortColumnForQuery(Builder $query, string $field, array $relations = [])
    {
        $relations ??= ($relantionName = $this->getRelationshipName($field)) ? explode('.', $relantionName) : [];

        if (! $relations) {
            return $field;
        }

        $currentRelationName = array_shift($relations);

        $relation = $this->getRelationShip($query->getModel(), $currentRelationName);

        if (! $relation) {

            $relantionName = explode('.', $field);

            [$relantionName, $field] = array_pad($relantionName, 2, null);

            return (string) str($relantionName)->plural()->append('.', $field);
        }

        $relationQuery = $relation->getRelated()::query();

        return $relation
            ->getRelationExistenceQuery(
                $relationQuery,
                $query,
                [$currentRelationName => $this->getSortColumnForQuery($relationQuery, $field, $relations)]
            )->applyScopes()->getQuery();
    }
}
