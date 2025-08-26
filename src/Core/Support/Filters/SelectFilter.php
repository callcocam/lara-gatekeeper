<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Filters;

use Callcocam\LaraGatekeeper\Core\Support\Filter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class SelectFilter extends Filter
{
    protected string $component = 'SelectFilter';
    protected ?string $relationshipName = null;
    protected ?string $relationshipTitleColumn = null;
    protected ?string $relationshipValueColumn = null;
    protected Closure|string|null $modelClassUnsure = null;
    protected ?array $fields = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label);

        // Criar um filtro padrão inteligente capaz de reconhecer se é um relacionamento
        $this->formatUsing(function (Builder $query, $value) {
            // Normalizar valor para array se for múltiplo
            if ($this->isMultiple() && is_string($value)) {
                $value = explode(',', $value);
            }

            // Aplicar filtro baseado no tipo de relacionamento
            if ($this->relationshipName) {
                $this->applyRelationshipFilter($query, $value);
            } else {
                // Filtro direto na coluna
                if (is_array($value) && count($value) > 0) {
                    $query->whereIn($this->getName(), $value);
                } elseif (!is_array($value) && $value !== null) {
                    $query->where($this->getName(), $value);
                }
            }
        });
    }

    /**
     * Define um relacionamento para popular as opções automaticamente
     */
    public function relationship(string $relationshipName, string $titleColumn = 'name', string $valueColumn = 'id'): static
    {
        $this->relationshipName = $relationshipName;
        $this->relationshipTitleColumn = $titleColumn;
        $this->relationshipValueColumn = $valueColumn;
        return $this;
    }

    /**
     * Aplica filtro baseado em relacionamento
     */
    protected function applyRelationshipFilter(Builder $query, $value): void
    {
        if (is_array($value) && count($value) > 0) {
            $query->whereHas($this->relationshipName, function ($q) use ($value) {
                $q->whereIn($this->relationshipValueColumn, $value);
            });
        } elseif (!is_array($value) && $value !== null) {
            $query->whereHas($this->relationshipName, function ($q) use ($value) {
                $q->where($this->relationshipValueColumn, $value);
            });
        }
    }

    /**
     * Carrega opções automaticamente do relacionamento
     */
    protected function loadRelationshipOptions(): array
    {
        if (!$this->relationshipName) {
            return [];
        }

        try {
            // Tentar obter o modelo base (assumindo que existe um método para isso)
            $modelClass = $this->getModelClass();
            if (!$modelClass) {
                return [];
            }

            $relationshipMethod = $this->relationshipName;
            $model = new $modelClass;

            if (!method_exists($model, $relationshipMethod)) {
                return [];
            }

            $relation = $model->$relationshipMethod();
            $relatedModel = $relation->getRelated();

            return $relatedModel::select([
                $this->relationshipValueColumn . ' as value',
                $this->relationshipTitleColumn . ' as label'
            ])
                ->orderBy($this->relationshipTitleColumn)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            dd($e->getMessage());
            // Log do erro se necessário
            return [];
        }
    }

    /**
     * Método para definir o modelo base (pode ser sobrescrito)
     */
    protected function getModelClass(): ?string
    {
        // Este método pode ser sobrescrito para retornar a classe do modelo
        // ou você pode injetar o modelo via construtor se necessário
        if ($this->modelClassUnsure) {
            $result = $this->evaluate($this->modelClassUnsure);
            return is_string($result) ? $result : null;
        }

        $modelClass = sprintf('App\Models\%s::class', str($this->getName())->studly()->singular());
        if (class_exists($modelClass)) {
            return $modelClass;
        }
        return null;
    }

    public function modelClassUnsureUsing(Closure|string|null $modelClassUnsure): static
    {
        $this->modelClassUnsure = $modelClassUnsure;
        return $this;
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields;

        if (empty($this->fields)) {
            return $this;
        } 

        $this->component('SelectFilterWithFields');

        return $this;
    }

    public function getFields(): ?array
    {
       
        return array_map(function ($field) {
            return $field->toArray();
        }, $this->fields);
    }

    public function toArray(): array
    {
        $options = $this->getOptions();
        // Se não há opções definidas e há relacionamento, carrega automaticamente
        if (empty($options) && $this->relationshipName) {
            $options = $this->loadRelationshipOptions();
        }

        return array_merge(parent::toArray(), [
            'options' => $options,
            'relationship' => $this->relationshipName,
            'fields' => $this->getFields(),
        ]);
    }
}
