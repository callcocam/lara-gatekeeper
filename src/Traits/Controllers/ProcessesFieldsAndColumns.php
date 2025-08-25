<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Action;
use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

trait ProcessesFieldsAndColumns
{

    protected $columns = [];
    protected $actions = [];

    protected function getColumns(): array
    {
        return $this->columns;
    }

    protected function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Processa os campos definidos pelo método fields.
     * Converte objetos Field em arrays e filtra os nulos (condicionais não atendidas).
     */
    protected function processFields(?Model $model = null): array
    {
        $rawFields = $this->fields($model);
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
     * Processa as colunas da tabela
     */
    protected function processTableColumns(): array
    {
        $rawColumns = $this->columns();

        $tableColumns = array_map(fn(Column $column) => $column->toArray(), $rawColumns);

        $this->columns = $rawColumns;

        return array_values($tableColumns);
    }

    /**
     * Define as ações padrão para a tabela.
     * Pode ser sobrescrito por controllers filhos para lógica customizada.
     */
    protected function getDefaultTableActions(): array
    {

        $actions = [];
        if (Gate::allows($this->getSidebarMenuPermission('create'))) {
            $actions[] = Action::make('create')
                ->icon('Plus')
                ->color('primary')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('create')
                ->label('Criar');
        }
        return $actions;
    }

    /**
     * Retorna as ações processadas
     */
    protected function getTableActions(): array
    {
        $actions = array_merge($this->getImportOptions(), $this->getExportOptions(), $this->getDefaultTableActions());

        $this->actions = $actions;

        // Adicionar ações padrão se não estiverem definidas
        return collect($actions)->map(function ($action) {
            return $action->toArray();
        })->toArray();
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
                    $field['labelAttribute'],
                    $field['valueAttribute']
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
        return [];
    }
}
