<?php

/**
 * Campo de Relacionamento Base
 * 
 * Esta classe implementa um campo de formulário base para relacionamentos,
 * fornecendo funcionalidades comuns para campos relacionados.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToFields;
use Closure;

class RelationshipField extends Field
{
    use BelongsToFields;

    /**
     * Callback para filtrar opções do relacionamento
     * 
     * @var Closure|null
     */
    protected ?Closure $filterCallback = null;

    /**
     * Construtor da classe
     * 
     * @param string $key Chave identificadora do campo
     * @param string $label Rótulo do campo
     */
    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('relationship');
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ==========================================

    /**
     * Define o relacionamento e seus atributos
     * 
     * @param string $relationship Nome do relacionamento
     * @param string $displayAttribute Atributo para exibição
     * @param string $valueAttribute Atributo para valor
     * @return static
     */
    public function relationship(string $relationship, string $displayAttribute = 'name', string $valueAttribute = 'id'): static
    {
        $this->relationship = $relationship;
        $this->labelAttribute = $displayAttribute;
        $this->valueAttribute = $valueAttribute;
        
        return $this;
    }

    /**
     * Define um callback para filtrar as opções do relacionamento
     * 
     * @param Closure $callback Função de filtro
     * @return static
     */
    public function filter(Closure $callback): static
    {
        $this->filterCallback = $callback;
        return $this;
    }

    // ==========================================
    // MÉTODOS DE ACESSO (GETTERS)
    // ==========================================

    /**
     * Retorna o nome do relacionamento
     * 
     * @return string|null
     */
    public function getRelationshipName(): ?string
    {
        return $this->relationship;
    }

    /**
     * Retorna o atributo de exibição
     * 
     * @return string
     */
    public function getDisplayAttribute(): string
    {
        return $this->labelAttribute;
    }

    /**
     * Retorna o atributo de valor
     * 
     * @return string
     */
    public function getValueAttribute(): string
    {
        return $this->valueAttribute;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO
    // ==========================================

    /**
     * Carrega as opções do relacionamento
     * 
     * @return static
     */
    public function loadOptions(): static
    {
        if (!$this->relationship || !$this->getModel()) {
            return $this;
        }

        try {
            $query = $this->getModel()->{$this->relationship}();

            // Aplica filtro se configurado
            if ($this->filterCallback) {
                $query = $this->evaluate($this->filterCallback, ['query' => $query]);
            }

            $options = $query->pluck($this->labelAttribute, $this->valueAttribute)->toArray();
            $this->options = $options;
        } catch (\Exception $e) {
            // Em caso de erro, define opções vazias
            $this->options = [];
        }

        return $this;
    }

    /**
     * Converte o campo para array
     * 
     * @return array Representação do campo em array
     */
    public function toArray($model = null): array
    {
        // Carrega opções se relacionamento estiver configurado
        if ($this->relationship) {
            $this->loadOptions();
        }

        return parent::toArray($model);
    }
} 