<?php

/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait para lidar com relacionamentos em colunas, permitindo suporte a
 * relacionamentos aninhados e definição explícita de relacionamentos.
 */
trait HasRelationship
{
    /**
     * Define ou resolve o relacionamento explicitamente.
     */
    protected Closure|string|null $relationShip = null;

    /**
     * Nome do relacionamento explicitamente definido.
     */
    protected ?string $relationshipName = null;

    /**
     * Define o nome do relacionamento explicitamente.
     *
     * @param string|null $relationshipName
     * @return $this
     */
    public function relationshipName(?string $relationshipName): self
    {
        $this->relationshipName = $relationshipName;
        return $this;
    }

    /**
     * Obtém o nome do relacionamento explicitamente definido.
     *
     * @return string|null
     */
    public function getRelationshipName(): ?string
    {
        return $this->relationshipName;
    }

    /**
     * Verifica se o nome do relacionamento foi definido.
     *
     * @return bool
     */
    public function hasRelationshipName(): bool
    {
        return !blank($this->relationshipName);
    }

    /**
     * Define um relacionamento (closure ou string).
     *
     * @param Closure|string|null $relationShip
     * @return $this
     */
    public function relationShip(Closure|string|null $relationShip): self
    {
        $this->relationShip = $relationShip;
        return $this;
    }

    /**
     * Obtém o relacionamento baseado no registro e nome da coluna.
     *
     * @param Model $record O registro atual.
     * @param string $name Nome do campo ou relacionamento.
     * @return mixed
     */
    public function getRelationShip(Model $record, string $name)
    {
        // Se o relacionamento foi explicitamente definido como string ou closure
        if (!blank($this->relationShip)) {
            return is_string($this->relationShip)
                ? $record->{$this->relationShip}()
                : $this->evaluate($this->relationShip);
        }

        // Se o nome não está definido e não contém "."
        if (blank($name) && !str($this->getName())->contains('.')) {
            return null;
        }

        // Resolver relacionamentos aninhados (dot notation)
        $relationship = null;
        foreach (explode('.', $name) as $nestedRelationshipName) {
            if (!$record->isRelation($nestedRelationshipName)) {
                $relationship = null;
                break;
            }
            $relationship = $record->{$nestedRelationshipName}();
        }

        return $relationship;
    }

    /**
     * Verifica se um relacionamento foi definido.
     *
     * @return bool
     */
    public function isRelation(): bool
    {
        return !blank($this->relationShip);
    }
}
