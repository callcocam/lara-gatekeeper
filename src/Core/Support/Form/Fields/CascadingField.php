<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Concerns\BelongsToFields;
use Closure;

class CascadingField extends Field
{
    use BelongsToFields;

    /**
     * Configuração dos campos dependentes
     */
    protected array $cascadingFields = [];


    protected Closure $queryCallback;


    public function __construct(string $key, string $label)
    {
        parent::__construct($key, $label);
        $this->type('multiLevelSelect');
        $this->queryCallback(function ($request) {
            return $request->all();
        });
    }

    public function queryCallback(Closure $queryCallback): static
    {
        $this->queryCallback = $queryCallback;
        return $this;
    }

    /**
     * Configura múltiplos campos dependentes
     */
    public function cascadingFields(array $fields): static
    {
        foreach ($fields as $fieldName => $config) {
            if (is_string($config)) {
                // Configuração simples: apenas nome do campo pai
                $this->cascadingField($fieldName, $config);
            } elseif (is_array($config)) {
                // Configuração detalhada
                $this->cascadingField(
                    $fieldName,
                    $config['parent_field'],
                    $config['model'] ?? null,
                    $config['query_callback'] ?? null
                );
            }
        }

        return $this;
    }

    public function getCascadingFields(): array
    {
        return $this->cascadingFields;
    }

    public function getCascadingField(string $fieldName): ?string
    {
        return $this->cascadingFields[$fieldName] ?? null;
    }


    /**
     * Configura um campo dependente
     */
    public function cascadingField(
        string $fieldName,
        string $parentField
    ): static {
        $this->cascadingFields[$fieldName] =  $parentField;

        return $this;
    }

    public function toArray($model = null): array
    {
        if (!count($this->cascadingFields)) {
            collect($this->fields)->map(function (Field $field) use ($model) {
                //verifica se o campo é um campo dependente, pegar pela order, se 0 pega o proximo campo e assim por diante
                $order = collect($this->fields)->search($field);
                $nextField = collect($this->fields)->get($order + 1);
                if ($nextField) {
                    $this->cascadingField($field->getName(), $nextField->getName());
                }
            });
        }

        foreach ($this->cascadingFields as $fieldName => $parentField) {
            if ($field = collect($this->fields)->firstWhere('name', $parentField)) {

                $options = $this->evaluate($this->queryCallback, [
                    'request' => data_get($this->getModel(), sprintf('%s.%s', $this->getName(), $fieldName)),
                ]);
                $field->options($options);
            }
        }

        // Corrigido: retorna os nomes dos campos, não as chaves numéricas
        $cascadingFieldNames = array_values($this->cascadingFields);
        sort($cascadingFieldNames);

        return array_merge(parent::toArray(), [
            'fields' => $this->getFieldsForForm(),
            'cascadingFields' => $cascadingFieldNames, 
        ]);
    }
}
