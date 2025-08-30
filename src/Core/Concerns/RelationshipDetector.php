<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

trait RelationshipDetector
{
    /**
     * Relacionamento configurado manualmente
     */
    protected ?string $manualRelationship = null;

    /**
     * Coluna para exibição do relacionamento
     */
    protected string $displayColumn = 'name';

    /**
     * Chave primária do relacionamento
     */
    protected string $relationshipKey = 'id';

    /**
     * Configuração manual do relacionamento
     */
    public function relationship(string $relationship, string $displayColumn = 'name', string $key = 'id'): static
    {
        $this->manualRelationship = $relationship;
        $this->displayColumn = $displayColumn;
        $this->relationshipKey = $key;
        
        return $this;
    }

    /**
     * Detecta relacionamentos automaticamente baseado no modelo
     */
    protected function detectRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->isRelationshipMethod($method, $model)) {
                $relationships[] = $method->getName();
            }
        }
        
        return $relationships;
    }

    /**
     * Verifica se um método é um relacionamento
     */
    protected function isRelationshipMethod(ReflectionMethod $method, Model $model): bool
    {
        // Ignora métodos mágicos e métodos herdados
        if ($method->isStatic() || $method->isAbstract() || $method->isConstructor()) {
            return false;
        }

        try {
            $returnType = $method->getReturnType();
            if (!$returnType) {
                return false;
            }

            $returnTypeName = $returnType->getName();
            
            // Verifica se retorna um tipo de relacionamento
            if (is_subclass_of($returnTypeName, Relation::class)) {
                return true;
            }

            // Verifica se o método retorna um relacionamento quando executado
            $result = $method->invoke($model);
            return $result instanceof Relation;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtém o relacionamento ativo (manual ou detectado)
     */
    protected function getActiveRelationship(): ?string
    {
        return $this->manualRelationship;
    }

    /**
     * Obtém a coluna de exibição
     */
    protected function getDisplayColumn(): string
    {
        return $this->displayColumn;
    }

    /**
     * Obtém a chave do relacionamento
     */
    protected function getRelationshipKey(): string
    {
        return $this->relationshipKey;
    }

    /**
     * Verifica se o relacionamento existe no modelo
     */
    protected function relationshipExists(Model $model, string $relationship): bool
    {
        return method_exists($model, $relationship);
    }

    /**
     * Obtém o tipo de relacionamento
     */
    protected function getRelationshipType(Model $model, string $relationship): ?string
    {
        if (!$this->relationshipExists($model, $relationship)) {
            return null;
        }

        try {
            $relation = $model->{$relationship}();
            return get_class($relation);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém opções de um relacionamento específico
     */
    protected function getRelationshipOptions(Model $model, string $relationship, string $column = null, string $key = null): array
    {
        if (!$this->relationshipExists($model, $relationship)) {
            return [];
        }

        $column = $column ?: $this->getDisplayColumn();
        $key = $key ?: $this->getRelationshipKey();

        try {
            $relation = $model->{$relationship}();
            
            // Para relacionamentos belongsTo, hasOne, hasMany
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
} 