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

use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToFields;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToId;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToName;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToLabel;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToIcon;
use Callcocam\LaraGatekeeper\Core\Concerns\FactoryPattern;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToOrder;
use Callcocam\LaraGatekeeper\Core\Concerns\EvaluatesClosures;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Closure;

class Tab
{
    use FactoryPattern;
    use EvaluatesClosures;
    use BelongsToId;
    use BelongsToName;
    use BelongsToLabel;
    use BelongsToIcon;
    use BelongsToOrder;
    use BelongsToFields;


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
     * Se a aba está visível
     * 
     * @var bool
     */
    protected bool $visible = true;

    /**
     * Callback para obter o valor da aba
     * 
     * @var ?Closure
     */
    protected ?Closure $valueCallback = null;

    /**
     * Construtor da classe
     * 
     * @param string $name Nome interno da aba
     * @param string $label Rótulo exibido na aba
     */
    public function __construct(string $name, string $label)
    {
        $this->name($name);
        $this->label($label ?? str($name)->ucfirst()->toString());
    }

    // ==========================================
    // MÉTODOS DE CONFIGURAÇÃO
    // ========================================== 

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

    public function getValue($initialValue = null): mixed
    {
        if ($this->valueCallback) {
            return $this->evaluate($this->valueCallback, ['initialValue' => $initialValue]);
        }
        if ($fields = $this->getFields()) {
            foreach ($fields as $field) {
                $initialValue[$field->getName()] = $field->getValue($initialValue);
            }
        }
        return  $initialValue;
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

    /**
     * Retorna se a aba está visível
     * 
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function valueCallback(Closure $valueCallback): static
    {
        $this->valueCallback = $valueCallback;
        return $this;
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
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'active' => $this->isActive(),
            'disabled' => $this->isDisabled(),
            'fields' => [],
        ];

        // Adiciona ícone se configurado
        if ($this->icon) {
            $data['icon'] = $this->icon;
        }

        // Processa os campos
        foreach ($this->fields as $field) {
            $fieldData = $field->id(sprintf('%s.%s', $this->getName(), $field->getName()));
            if ($fieldData) {
                $data['fields'][] = $fieldData->toArray();
            }
        }

        return $data;
    }
}
