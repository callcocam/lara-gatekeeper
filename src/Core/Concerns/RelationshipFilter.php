<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait RelationshipFilter
{
    /**
     * Filtros baseados em relacionamentos
     */
    protected array $relationshipFilters = [];

    /**
     * Adiciona um filtro baseado em relacionamento
     */
    public function filterByRelationship(string $relationship, Closure|string $callback, string $operator = '='): static
    {
        $this->relationshipFilters[$relationship] = [
            'callback' => $callback,
            'operator' => $operator
        ];
        
        return $this;
    }

    /**
     * Filtra por relacionamento com valor específico
     */
    public function whereRelationship(string $relationship, string $column, mixed $value, string $operator = '='): static
    {
        return $this->filterByRelationship($relationship, function (Builder $query) use ($column, $value, $operator) {
            return $query->where($column, $operator, $value);
        });
    }

    /**
     * Filtra por relacionamento com múltiplos valores
     */
    public function whereRelationshipIn(string $relationship, string $column, array $values): static
    {
        return $this->filterByRelationship($relationship, function (Builder $query) use ($column, $values) {
            return $query->whereIn($column, $values);
        });
    }

    /**
     * Filtra por relacionamento com busca LIKE
     */
    public function whereRelationshipLike(string $relationship, string $column, string $value): static
    {
        return $this->filterByRelationship($relationship, function (Builder $query) use ($column, $value) {
            return $query->where($column, 'LIKE', "%{$value}%");
        });
    }

    /**
     * Filtra por relacionamento com callback customizado
     */
    public function whereRelationshipCallback(string $relationship, Closure $callback): static
    {
        return $this->filterByRelationship($relationship, $callback);
    }

    /**
     * Aplica todos os filtros de relacionamento em uma query
     */
    protected function applyRelationshipFilters(Builder $query, Model $model): Builder
    {
        foreach ($this->relationshipFilters as $relationship => $filter) {
            if (method_exists($model, $relationship)) {
                $callback = $filter['callback'];
                
                if ($callback instanceof Closure) {
                    $query->whereHas($relationship, $callback);
                } else {
                    // Se for string, assume que é uma coluna para filtrar
                    $query->whereHas($relationship, function (Builder $relationQuery) use ($callback, $filter) {
                        $relationQuery->where($callback, $filter['operator'], request($callback));
                    });
                }
            }
        }
        
        return $query;
    }

    /**
     * Filtra por relacionamento não existente
     */
    public function whereDoesntHaveRelationship(string $relationship, Closure $callback = null): static
    {
        $this->relationshipFilters[$relationship] = [
            'callback' => $callback ?: function (Builder $query) {
                return $query;
            },
            'type' => 'doesnt_have'
        ];
        
        return $this;
    }

    /**
     * Aplica filtros de relacionamento incluindo whereDoesntHave
     */
    protected function applyAllRelationshipFilters(Builder $query, Model $model): Builder
    {
        foreach ($this->relationshipFilters as $relationship => $filter) {
            if (!method_exists($model, $relationship)) {
                continue;
            }

            $callback = $filter['callback'];
            
            if (isset($filter['type']) && $filter['type'] === 'doesnt_have') {
                $query->whereDoesntHave($relationship, $callback);
            } else {
                $query->whereHas($relationship, $callback);
            }
        }
        
        return $query;
    }

    /**
     * Limpa todos os filtros de relacionamento
     */
    public function clearRelationshipFilters(): static
    {
        $this->relationshipFilters = [];
        return $this;
    }

    /**
     * Obtém os filtros de relacionamento configurados
     */
    public function getRelationshipFilters(): array
    {
        return $this->relationshipFilters;
    }

    /**
     * Verifica se há filtros de relacionamento configurados
     */
    public function hasRelationshipFilters(): bool
    {
        return !empty($this->relationshipFilters);
    }
} 