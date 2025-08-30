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

/**
 * Trait principal para gerenciamento de relacionamentos
 * Combina detecção automática, filtros e opções
 */
trait RelationshipManager
{
    use RelationshipDetector, RelationshipFilter, BelongsToOptions;

    /**
     * Configuração rápida de relacionamento com filtros
     */
    public function withRelationship(
        string $relationship,
        string $displayColumn = 'name',
        string $key = 'id',
        ?Closure $filterCallback = null
    ): static {
        // Configura o relacionamento
        $this->viaRelationship($relationship, $displayColumn, $key);
        
        // Adiciona filtro se fornecido
        if ($filterCallback) {
            $this->filterByRelationship($relationship, $filterCallback);
        }
        
        return $this;
    }

    /**
     * Busca automática por relacionamento
     */
    public function searchByRelationship(
        string $relationship,
        string $searchColumn,
        string $searchTerm,
        string $displayColumn = 'name',
        string $key = 'id'
    ): static {
        $this->viaRelationship($relationship, $displayColumn, $key);
        
        $this->filterByRelationship($relationship, function (Builder $query) use ($searchColumn, $searchTerm) {
            return $query->where($searchColumn, 'LIKE', "%{$searchTerm}%");
        });
        
        return $this;
    }

    /**
     * Filtra por múltiplos relacionamentos
     */
    public function filterByMultipleRelationships(array $relationships): static
    {
        foreach ($relationships as $relationship => $config) {
            if (is_string($config)) {
                // Configuração simples: apenas nome do relacionamento
                $this->filterByRelationship($config, function (Builder $query) {
                    return $query;
                });
            } elseif (is_array($config)) {
                // Configuração detalhada
                $callback = $config['callback'] ?? function (Builder $query) {
                    return $query;
                };
                
                $this->filterByRelationship($relationship, $callback);
            }
        }
        
        return $this;
    }

    /**
     * Aplica todos os filtros e relacionamentos em uma query
     */
    public function applyToQuery(Builder $query, Model $model): Builder
    {
        // Aplica filtros de relacionamento
        $query = $this->applyAllRelationshipFilters($query, $model);
        
        return $query;
    }

    /**
     * Obtém todas as opções de relacionamentos configurados
     */
    public function getAllRelationshipOptions(Model $model): array
    {
        $options = [];
        
        if ($this->activeRelationship) {
            $options[$this->activeRelationship] = $this->getOptionsFromModel($model);
        }
        
        if ($this->manualRelationship) {
            $options[$this->manualRelationship] = $this->getRelationshipOptions(
                $model,
                $this->manualRelationship,
                $this->displayColumn,
                $this->relationshipKey
            );
        }
        
        return $options;
    }

    /**
     * Reseta todas as configurações de relacionamento
     */
    public function resetRelationships(): static
    {
        $this->activeRelationship = null;
        $this->manualRelationship = null;
        $this->displayColumn = 'name';
        $this->primaryKey = 'id';
        $this->clearRelationshipFilters();
        
        return $this;
    }

    /**
     * Verifica se há relacionamentos configurados
     */
    public function hasRelationships(): bool
    {
        return $this->activeRelationship || $this->manualRelationship || $this->hasRelationshipFilters();
    }

    /**
     * Obtém estatísticas dos relacionamentos configurados
     */
    public function getRelationshipStats(): array
    {
        return [
            'active_relationship' => $this->activeRelationship,
            'manual_relationship' => $this->manualRelationship,
            'display_column' => $this->displayColumn,
            'primary_key' => $this->primaryKey,
            'filters_count' => count($this->relationshipFilters),
            'is_multiple' => $this->isMultiple(),
        ];
    }
} 