<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOptions
{
    use RelationshipDetector, RelationshipFilter;

    /**
     * The options for the filter.
     *
     * @var array
     */
    protected Closure|array $options = [];

    /**
     * Relacionamento ativo para busca
     */
    protected ?string $activeRelationship = null;

    /**
     * Coluna para exibição
     */
    protected string $displayColumn = 'name';

    /**
     * Chave primária
     */
    protected string $primaryKey = 'id';

    /**
     * Múltipla seleção
     */
    protected Closure|bool|null $multiple = false;

    /**
     * Obtém o relacionamento ativo para opções
     */
    protected function getOptionsActiveRelationship(): ?string
    {
        return $this->activeRelationship ?: $this->manualRelationship;
    }

    /**
     * Obtém opções do modelo baseado no relacionamento
     */
    protected function getOptionsFromModel(Model $model, ?string $column = null, ?string $key = null): array
    {
        $relationship = $this->getOptionsActiveRelationship();
        $column = $column ?: $this->displayColumn;
        $key = $key ?: $this->primaryKey;

        if (!$relationship || !method_exists($model, $relationship)) {
            return [];
        }

        try {
            $relation = $model->{$relationship}();
            
            if (method_exists($relation, 'getRelated')) {
                $relatedModel = $relation->getRelated();
                
                return $relatedModel::orderBy($column)
                    ->pluck($column, $key)
                    ->toArray();
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set the options for the filter.
     *
     * @param array $options
     * @return static
     */
    public function options(Model|Closure|array $options = [], ?string $column = null, ?string $key = null): static
    {
        if ($options instanceof Model) {
            $options = $this->getOptionsFromModel($options, $column, $key);
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Configura o relacionamento para busca automática
     */
    public function viaRelationship(string $relationship, string $displayColumn = 'name', string $key = 'id'): static
    {
        $this->activeRelationship = $relationship;
        $this->displayColumn = $displayColumn;
        $this->primaryKey = $key;
        
        return $this;
    }

    /**
     * Configura o relacionamento usando o método relationship do RelationshipDetector
     */
    public function setRelationship(string $relationship, string $displayColumn = 'name', string $key = 'id'): static
    {
        return $this->relationship($relationship, $displayColumn, $key);
    }
    /**
     * Get the options for the filter.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->evaluate($this->options, [
            'request' => request()
        ]);
    }

    public function multiple(bool|Closure $multiple = true): static
    {
        $this->multiple = $multiple;
        $this->component('SelectMultipleFilter');
        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->evaluate($this->multiple);
    }
}
