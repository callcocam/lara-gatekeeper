<?php

/**
 * Campo de Relacionamento Um-para-Muitos
 * 
 * Esta classe implementa um campo de formulário para relacionamentos hasMany,
 * permitindo adicionar, remover e gerenciar itens relacionados.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\RelationshipField;
use Closure;

class HasManyField extends RelationshipField
{
    /**
     * Construtor da classe
     * 
     * @param string $key Chave identificadora do campo
     * @param string $label Rótulo do campo
     */
    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('hasMany');
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ==========================================

    /**
     * Define se o campo permite múltiplas seleções
     * 
     * @param bool $multiple
     * @return static
     */
    public function multiple(bool $multiple = true): static
    {
        $this->inputProps['multiple'] = $multiple;
        return $this;
    }

    /**
     * Define se o campo permite adicionar novos itens
     * 
     * @param bool $allowAdd
     * @return static
     */
    public function allowAdd(bool $allowAdd = true): static
    {
        $this->inputProps['allowAdd'] = $allowAdd;
        return $this;
    }

    /**
     * Define se o campo permite remover itens
     * 
     * @param bool $allowRemove
     * @return static
     */
    public function allowRemove(bool $allowRemove = true): static
    {
        $this->inputProps['allowRemove'] = $allowRemove;
        return $this;
    }

    /**
     * Define se o campo permite editar itens existentes
     * 
     * @param bool $allowEdit
     * @return static
     */
    public function allowEdit(bool $allowEdit = true): static
    {
        $this->inputProps['allowEdit'] = $allowEdit;
        return $this;
    }

    /**
     * Define o número máximo de itens permitidos
     * 
     * @param int $maxItems
     * @return static
     */
    public function maxItems(int $maxItems): static
    {
        $this->inputProps['maxItems'] = $maxItems;
        return $this;
    }

    /**
     * Define se o campo deve mostrar um botão para adicionar novo item
     * 
     * @param bool $showAddButton
     * @return static
     */
    public function showAddButton(bool $showAddButton = true): static
    {
        $this->inputProps['showAddButton'] = $showAddButton;
        return $this;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO
    // ==========================================

    /**
     * Carrega as opções do relacionamento hasMany
     * 
     * @return static
     */
    public function loadOptions(): static
    {
        if (!$this->relationship || !$this->getModel()) {
            return $this;
        }

        try {
            // Para relacionamentos hasMany, carrega todos os itens relacionados
            $relatedItems = $this->getModel()->{$this->relationship};

            // Aplica filtro se configurado
            if ($this->filterCallback) {
                $relatedItems = $this->evaluate($this->filterCallback, ['query' => $relatedItems]);
            }

            // Converte para array de opções
            $options = $relatedItems->pluck($this->labelAttribute, $this->valueAttribute)->toArray();
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