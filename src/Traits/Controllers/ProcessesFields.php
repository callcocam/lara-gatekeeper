<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Field;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait responsável pelo processamento de fields
 * 
 * Gerencia:
 * - Processamento de fields para formulários
 * - Valores iniciais para edição e criação
 * - Resolução de relacionamentos
 * - Validação e filtragem de fields
 */
trait ProcessesFields
{
    /**
     * Processa os campos definidos pelo método fields.
     * Converte objetos Field em arrays e filtra os nulos (condicionais não atendidas).
     */
    protected function processFields(?Model $model = null, $rawFields = []): array
    {
        if (empty($rawFields)) {
            $rawFields = $this->fields($model);
        }
        $processedFields = [];

        if (!empty($rawFields) && $rawFields[0] instanceof Field) {
            $processedFields = array_map(fn(Field $field) => $field->toArray(), $rawFields);
            // Filtrar campos que retornaram null (condição não atendida)
            $processedFields = array_filter($processedFields, fn($field) => $field !== null);
        } elseif (is_array($rawFields)) {
            // Se já for um array de arrays, assumir que a lógica condicional
            // já foi tratada dentro do fields (menos ideal)
            $processedFields = $rawFields;
        } 
        // Reindexar array para evitar problemas com índices faltando no JS
        return array_values($processedFields);
    }

    /**
     * Resolve relacionamentos para formulários
     */
    public function resolveRelationship($relationship, $modelInstance, $labelAttribute = 'name', $valueAttribute = 'id'): array
    {
        $options = [];

        if ($relationship) {
            if (method_exists($modelInstance, $relationship)) {
                $options = $modelInstance->{$relationship}->pluck($valueAttribute)->toArray();
            }
        }

        return $options;
    }

    /**
     * Obtém os valores iniciais para o formulário de edição.
     * Pode ser sobrescrito por controllers filhos para lógica customizada.
     */
    protected function getInitialValuesForEdit(Model $modelInstance, array $fields = []): array
    {
        $values = $modelInstance->toArray();

        foreach ($fields as $field) {
            if (isset($field['relationship'])) {
                $values[$field['key']] = $this->resolveRelationship(
                    $field['relationship'],
                    $modelInstance,
                    $field['labelAttribute'] ?? 'name',
                    $field['valueAttribute'] ?? 'id'
                );
            }
        }

        return $values;
    }

    /**
     * Obtém valores iniciais para criação
     */
    protected function getInitialValuesForCreate(Model $modelInstance, array $fields = []): array
    {
        $values = [];

        // Preenche valores padrão dos fields
        foreach ($fields as $field) {
            if (isset($field['default'])) {
                $values[$field['key']] = $field['default'];
            }
        }

        return $values;
    }

    /**
     * Valida se um field deve ser incluído baseado em permissões
     */
    protected function shouldIncludeField(Field $field, ?Model $model = null): bool
    {
        // Verifica visibilidade do field
        return $field->isVisible($model);
    }

    /**
     * Filtra fields baseado em permissões e contexto
     */
    protected function filterFieldsByContext(array $fields, string $context, ?Model $model = null): array
    {
        return array_filter($fields, function ($field) use ($context, $model) {
            if (!($field instanceof Field)) {
                return true; // Mantém arrays normais
            }

            // Verifica contexto específico
            $isVisibleInContext = match ($context) {
                'create' => $field->isVisibleOnCreate(),
                'edit' => $field->isVisibleOnEdit(),
                'show' => $field->isVisibleOnShow(),
                default => true,
            };

            return $isVisibleInContext && $this->shouldIncludeField($field, $model);
        });
    }

    /**
     * Processa fields específicos para criação
     */
    protected function processFieldsForCreate(): array
    {
        $fields = $this->fields();
        $filteredFields = $this->filterFieldsByContext($fields, 'create');
        
        return $this->processFields();
    }

    /**
     * Processa fields específicos para edição
     */
    protected function processFieldsForEdit(Model $model): array
    {
        $fields = $this->fields($model);
        $filteredFields = $this->filterFieldsByContext($fields, 'edit', $model);
        
        return $this->processFields($model);
    }

    /**
     * Processa fields específicos para visualização
     */
    protected function processFieldsForShow(Model $model): array
    {
        $fields = $this->fields($model);
        $filteredFields = $this->filterFieldsByContext($fields, 'show', $model);
        
        return $this->processFields($model);
    }

    /**
     * Resolve valores de relacionamentos dinâmicos
     */
    protected function resolveRelationshipOptions(string $relationship, ?Model $model = null): array
    {
        if (!$model || !method_exists($model, $relationship)) {
            return [];
        }

        $relation = $model->{$relationship}();
        
        // Para diferentes tipos de relacionamento
        if (method_exists($relation, 'getResults')) {
            $results = $relation->getResults();
            
            if (is_iterable($results)) {
                return collect($results)->pluck('name', 'id')->toArray();
            }
        }

        return [];
    }

    /**
     * Aplica transformações nos valores dos fields
     */
    protected function transformFieldValues(array $values, array $fields): array
    {
        foreach ($fields as $field) {
            if (!isset($field['key']) || !isset($values[$field['key']])) {
                continue;
            }

            $key = $field['key'];
            $value = $values[$key];

            // Aplicar transformações baseadas no tipo do field
            $values[$key] = match ($field['type'] ?? 'text') {
                'date' => $this->transformDateValue($value),
                'datetime' => $this->transformDateTimeValue($value),
                'boolean' => $this->transformBooleanValue($value),
                'number' => $this->transformNumberValue($value),
                'json' => $this->transformJsonValue($value),
                default => $value
            };
        }

        return $values;
    }

    /**
     * Transformações específicas por tipo
     */
    protected function transformDateValue($value)
    {
        if (is_string($value)) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    protected function transformDateTimeValue($value)
    {
        if (is_string($value)) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
        }
        return $value;
    }

    protected function transformBooleanValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function transformNumberValue($value)
    {
        return is_numeric($value) ? (float) $value : $value;
    }

    protected function transformJsonValue($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Placeholder para método que deve ser implementado pelo controller
     */
    abstract protected function fields(?Model $model = null): array;
} 