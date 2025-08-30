<?php

/**
 * Classe Tab - Representa uma aba individual
 * 
 * Esta classe implementa uma aba que pode conter múltiplos campos
 * e ser organizada dentro de um TabsField.
 * 
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Field;

class Tab
{
    /**
     * Nome da aba
     * 
     * @var string
     */
    protected string $name;

    /**
     * Rótulo da aba
     * 
     * @var string
     */
    protected string $label;

    /**
     * Campos da aba
     * 
     * @var array
     */
    protected array $fields = [];

    /**
     * Ícone da aba (opcional)
     * 
     * @var string|null
     */
    protected ?string $icon = null;

    /**
     * Se a aba está ativa por padrão
     * 
     * @var bool
     */
    protected bool $active = false;

    /**
     * Se a aba está desabilitada
     * 
     * @var bool
     */
    protected bool $disabled = false;

    /**
     * Construtor da classe
     * 
     * @param string $name Nome interno da aba
     * @param string $label Rótulo exibido na aba
     */
    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ==========================================

    /**
     * Define o ícone da aba
     * 
     * @param string $icon Nome do ícone (Lucide)
     * @return static
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Define se a aba está ativa por padrão
     * 
     * @param bool $active
     * @return static
     */
    public function active(bool $active = true): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Define se a aba está desabilitada
     * 
     * @param bool $disabled
     * @return static
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Adiciona um campo à aba
     * 
     * @param Field $field Campo a ser adicionado
     * @return static
     */
    public function field(Field $field): static
    {
        $this->fields[] = $field;
        return $this;
    }

    /**
     * Adiciona múltiplos campos à aba
     * 
     * @param array $fields Array de campos
     * @return static
     */
    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $this->fields[] = $field;
            }
        }
        return $this;
    }

    // ==========================================
    // MÉTODOS DE ACESSO (GETTERS)
    // ==========================================

    /**
     * Retorna o nome da aba
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retorna o rótulo da aba
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Retorna os campos da aba
     * 
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Retorna o ícone da aba
     * 
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Retorna se a aba está ativa
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Retorna se a aba está desabilitada
     * 
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    // ==========================================
    // MÉTODOS DE PROCESSAMENTO
    // ==========================================

    /**
     * Converte a aba para array
     * 
     * @return array Representação da aba em array
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'active' => $this->active,
            'disabled' => $this->disabled,
            'fields' => [],
        ];

        // Adiciona ícone se configurado
        if ($this->icon) {
            $data['icon'] = $this->icon;
        }

        // Processa os campos
        foreach ($this->fields as $field) {
            $fieldData = $field->toArray();
            if ($fieldData) {
                $data['fields'][] = $fieldData;
            }
        }

        return $data;
    }
} 