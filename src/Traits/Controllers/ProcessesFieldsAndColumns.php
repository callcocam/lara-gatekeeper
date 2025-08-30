<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Illuminate\Database\Eloquent\Model; 

/**
 * Trait unificado que combina processamento de Fields, Columns, Actions e Import/Export
 * 
 * Este trait serve como interface unificada para manter compatibilidade
 * com controllers existentes, mas agora utiliza traits especializados.
 * 
 * Traits utilizados:
 * - ProcessesFields: Lógica de fields para formulários
 * - ProcessesColumns: Lógica de columns para tabelas  
 * - ProcessesActions: Lógica de actions (CRUD, custom)
 * - ProcessesImportExport: Lógica de import/export
 */
trait ProcessesFieldsAndColumns
{
    use ProcessesFields,
        ProcessesColumns,
        ProcessesActions,
        ProcessesImportExport;

    /**
     * Métodos auxiliares para facilitar o uso dos traits separados
     */

    /**
     * Configura actions padrão + customizadas de uma vez
     */
    protected function setupActions(): array
    {
        return array_merge(
            $this->getDefaultTableActions(),
            $this->getCrudActions(),
            $this->getCustomActions(),
            $this->getFooterActions()
        );
    }

    /**
     * Configura columns padrão + customizadas de uma vez
     */
    protected function setupColumns(): array
    {
        $columns = $this->columns();
        $filtered = $this->filterColumnsWithPermissions($columns);
        return $this->sortColumnsByOrder($filtered);
    }

    /**
     * Configura fields com filtragem por contexto
     */
    protected function setupFields(?Model $model = null, ?string $context = null): array
    {
        $context = $context ?? $this->getContext();
        $fields = $this->fields($model);
        
        if ($context) {
            $fields = $this->filterFieldsByContext($fields, $context, $model);
        }
        
        return $this->processFields($model);
    }

    /**
     * Métodos de conveniência para diferentes contextos
     */

    /**
     * Prepara dados completos para formulário de criação
     */
    protected function prepareCreateFormData(): array
    {
        return [
            'fields' => $this->processFieldsForCreate(),
            'actions' => $this->getTableActions(),
            'initial_values' => $this->getInitialValuesForCreate(new ($this->getModelClass()), []),
        ];
    }

    /**
     * Prepara dados completos para formulário de edição
     */
    protected function prepareEditFormData(Model $model): array
    {
        $fields = $this->processFieldsForEdit($model);
        
        return [
            'fields' => $fields,
            'actions' => $this->getTableActions(),
            'initial_values' => $this->getInitialValuesForEdit($model, $fields),
        ];
    }

    /**
     * Prepara dados completos para visualização
     */
    protected function prepareShowData(Model $model): array
    {
        return [
            'fields' => $this->processFieldsForShow($model),
            'actions' => $this->getTableActions(),
            'model' => $model,
        ];
    }

    /**
     * Prepara dados completos para listagem/tabela
     */
    protected function prepareIndexData(): array
    {
        return [
            'columns' => $this->processTableColumns(),
            'actions' => $this->getTableActions(),
            'headers' => $this->getTableHeaders(),
        ];
    }

    /**
     * Placeholder para métodos que devem ser implementados pelo controller
     */
    abstract protected function fields(?Model $model = null): array;
    abstract protected function columns(): array;
    abstract protected function getRouteNameBase(): string;

    /**
     * Método opcional para obter a classe do modelo
     * Pode ser implementado pelo controller para facilitar operações
     */
    protected function getModelClass(): string
    {
        // Por padrão, tenta derivar da classe do controller
        $controllerClass = static::class;
        $modelName = str_replace('Controller', '', class_basename($controllerClass));
        
        return "App\\Models\\{$modelName}";
    }
}
