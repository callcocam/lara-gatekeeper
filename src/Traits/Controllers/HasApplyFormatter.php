<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Cast\CastRegistry;

trait HasApplyFormatter
{
    /**
     * Inicializa o CastRegistry na primeira utilização
     */
    protected function initializeCastRegistry(): void
    {
        CastRegistry::initialize();
    }

    /**
     * Aplica formatação automática nos resultados
     * 
     * Processa três tipos de formatação:
     * - Colunas de relacionamento
     * - Colunas padrão da tabela
     * - Actions/botões formatados
     * 
     * @param mixed $item Item a ser formatado
     * @return object Item com valores formatados adicionais
     */
    protected function applyAutoFormatting($item): object
    {
        $this->initializeCastRegistry();

        if (!is_object($item)) {
            return $item;
        }

        // Formata colunas de relacionamento
        $this->formatRelationshipColumns($item);

        // Formata colunas padrão
        $this->formatTableColumns($item);

        // Formata actions/botões
        $this->formatActions($item);

        return $item;
    }

    /**
     * Formata colunas de relacionamento
     * Adiciona versões formatadas das colunas com sufixo '_formatted'
     */
    private function formatRelationshipColumns(&$item): void
    {
        foreach ($this->relationshipColumns as $column) {
            $columnName = data_get($column, 'name');
            $value = $this->getRelationshipValue($item, $columnName);

            $formattedValue = $this->formatColumnValue($value, $columnName);
            if ($formattedValue !== null) {
                $this->setRelationshipValue($item, $columnName . '_formatted', $formattedValue);
            }
        }
    }

    /**
     * Formata colunas padrão da tabela
     */
    private function formatTableColumns(&$item): void
    {
        $columns = $this->getColumns();
        if (!$columns) return;
        foreach ($columns as $column) {
            if ($column->hasActions()) {
                $actions = [];
                foreach ($column->getActions() as $action) {
                    $actionName = $action->getName();
                    $formatted = $action->render($item);
                    $actions[$actionName] = $formatted;
                }
                $this->setModelValue($item, $column->getName(), $actions);
            } else {
                $key = $column->getAccessorKey();
                if (!$key) continue;
                $value = data_get($item, $key);
                $formatteds[$key] = $value;
                $formattedValue = $this->formatColumnValue($value, $key);

                if ($formattedValue !== null) {

                    $this->setModelValue($item, $key . '_formatted', $formattedValue);
                }
            }
        }
    }

    /**
     * Formata actions/botões
     */
    private function formatActions(&$item): void
    {
        $actions = $this->getActions();
        if (!$actions) return;
        foreach ($actions as $action) {
            $actionName = $action->getName();

            // Prioriza método format() se disponível
            if (method_exists($action, 'format')) {
                $formatted = $action->format($item);
            } else {
                $formatted = $action->render($item);
            }

            $this->setModelValue($item, $actionName . '_formatted', $formatted);
        }
    }

    /**
     * Formata um valor usando coluna personalizada ou CastRegistry
     * 
     * @param mixed $value Valor original
     * @param string $columnName Nome da coluna
     * @return mixed|null Valor formatado ou null se valor original for null
     */
    private function formatColumnValue($value, string $columnName)
    {
        if ($value === null) {
            return null;
        }

        // Tenta usar formatação personalizada da coluna
        $customColumn = $this->getColumnByNameFormat($columnName);
        if ($customColumn) {
            return $customColumn->format($value);
        }

        // Usa formatação automática do CastRegistry
        return CastRegistry::autoFormat($value, $columnName);
    }

    /**
     * Define valor formatado para coluna de relacionamento
     * Usa separador configurado para navegar na estrutura aninhada
     */
    protected function setRelationshipValue($model, string $column, $value): void
    {
        $separator = config('lara-gatekeeper.table.relationship_separator', '.');
        $path = str_replace($separator, '.', $column);
        data_set($model, $path, $value);
    }

    /**
     * Obtém valor de coluna de relacionamento
     * Usa separador configurado para navegar na estrutura aninhada
     */
    protected function getRelationshipValue($model, string $column)
    {
        $separator = config('lara-gatekeeper.table.relationship_separator', '.');
        $path = str_replace($separator, '.', $column);
        return data_get($model, $path);
    }

    /**
     * Busca coluna com formatação personalizada pelo nome
     * 
     * @param string $name Nome da coluna
     * @return mixed|null Coluna encontrada ou null
     */
    protected function getColumnByNameFormat(string $name)
    {
        $columns = $this->getColumns();
        if (!$columns) return null;

        foreach ($columns as $column) {
            if ($column->getName() === $name && $column->isFormatted()) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Define valor formatado diretamente no modelo
     */
    protected function setModelValue(&$model, string $column, $value): void
    {
        data_set($model, $column, $value);
    }
}
