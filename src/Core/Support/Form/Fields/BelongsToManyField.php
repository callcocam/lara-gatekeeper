<?php

/**
 * Campo de Relacionamento Muitos-para-Muitos
 * 
 * Esta classe implementa um campo de formulário para relacionamentos belongsToMany,
 * permitindo seleção múltipla de itens relacionados.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Fields\RelationshipField;

class BelongsToManyField extends RelationshipField
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
        $this->type('belongsToMany');
        $this->inputProps['multiple'] = true; // Sempre múltiplo para belongsToMany
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ==========================================

    /**
     * Define se o campo deve permitir busca
     * 
     * @param bool $searchable
     * @return static
     */
    public function searchable(bool $searchable = true): static
    {
        $this->inputProps['searchable'] = $searchable;
        return $this;
    }

    /**
     * Define se o campo deve mostrar tags
     * 
     * @param bool $showTags
     * @return static
     */
    public function showTags(bool $showTags = true): static
    {
        $this->inputProps['showTags'] = $showTags;
        return $this;
    }

    /**
     * Define se o campo deve permitir criar novos itens
     * 
     * @param bool $allowCreate
     * @return static
     */
    public function allowCreate(bool $allowCreate = true): static
    {
        $this->inputProps['allowCreate'] = $allowCreate;
        return $this;
    }

    /**
     * Define o número máximo de itens selecionáveis
     * 
     * @param int $maxSelection
     * @return static
     */
    public function maxSelection(int $maxSelection): static
    {
        $this->inputProps['maxSelection'] = $maxSelection;
        return $this;
    }

    /**
     * Define se o campo deve mostrar contador de itens
     * 
     * @param bool $showCounter
     * @return static
     */
    public function showCounter(bool $showCounter = true): static
    {
        $this->inputProps['showCounter'] = $showCounter;
        return $this;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO
    // ==========================================

    /**
     * Carrega as opções do relacionamento belongsToMany
     * 
     * @return static
     */
    public function loadOptions(): static
    {
        if (!$this->relationship || !$this->getModel()) {
            return $this;
        }

        try {
            // Para relacionamentos belongsToMany, busca todos os itens disponíveis
            $availableItems = $this->getModel()->{$this->relationship}();

            // Aplica filtro se configurado
            if ($this->filterCallback) {
                $availableItems = $this->evaluate($this->filterCallback, ['query' => $availableItems]);
            }

            // Converte para array de opções
            $options = $availableItems->pluck($this->labelAttribute, $this->valueAttribute)->toArray();
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