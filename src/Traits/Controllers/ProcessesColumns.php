<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Column;

/**
 * Trait responsável pelo processamento de columns
 * 
 * Gerencia:
 * - Processamento de columns para tabelas
 * - Headers customizados
 * - Relacionamentos em columns
 * - Visibilidade e filtragem de columns
 */
trait ProcessesColumns
{
    protected array $columns = [];
    protected array $headers = [];
    protected array $relationshipColumns = [];

    /**
     * Processa as colunas da tabela
     */
    protected function processTableColumns(): array
    {
        $rawColumns = $this->columns();

        $tableColumns = array_map(function (Column $column) {
            if (str_contains($column->getName(), '.')) {
                $this->relationshipColumns[] = $column->getName();
            }
            return $column->toArray();
        }, $rawColumns);

        $this->columns = $rawColumns;

        return array_values($tableColumns);
    }

    /**
     * Obtém as columns processadas
     */
    protected function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Obtém os headers customizados
     */
    protected function getTableHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Obtém columns de relacionamento
     */
    protected function getRelationshipColumns(): array
    {
        return $this->relationshipColumns;
    }

    /**
     * Filtra columns baseado em permissões
     */
    protected function filterColumnsWithPermissions(array $columns): array
    {
        return array_filter($columns, function (Column $column) {
            return $column->isVisible();
        });
    }

    /**
     * Adiciona column personalizada
     */
    protected function addCustomColumn(string $name, string $label, ?string $type = 'text'): Column
    {
        return Column::make($name, $label)
            ->type($type)
            ->sortable()
            ->searchable();
    }

    /**
     * Cria column de ações
     */
    protected function makeActionsColumn(): Column
    {
        return Column::make('actions', 'Ações')
            ->type('actions')
            ->sortable(false)
            ->searchable(false)
            ->width('120px')
            ->align('center');
    }

    /**
     * Cria column de status
     */
    protected function makeStatusColumn(): Column
    {
        return Column::make('status', 'Status')
            ->type('badge')
            ->sortable()
            ->searchable()
            ->width('100px')
            ->align('center');
    }

    /**
     * Cria column de data
     */
    protected function makeDateColumn(string $field, string $label): Column
    {
        return Column::make($field, $label)
            ->type('date')
            ->sortable()
            ->searchable()
            ->width('120px')
            ->format('d/m/Y');
    }

    /**
     * Cria column de data e hora
     */
    protected function makeDateTimeColumn(string $field, string $label): Column
    {
        return Column::make($field, $label)
            ->type('datetime')
            ->sortable()
            ->searchable()
            ->width('160px')
            ->format('d/m/Y H:i');
    }

    /**
     * Cria column de relacionamento
     */
    protected function makeRelationshipColumn(string $relationship, string $field, string $label): Column
    {
        return Column::make("{$relationship}.{$field}", $label)
            ->type('relationship')
            ->sortable()
            ->searchable();
    }

    /**
     * Cria column de imagem/avatar
     */
    protected function makeImageColumn(string $field, string $label): Column
    {
        return Column::make($field, $label)
            ->type('image')
            ->sortable(false)
            ->searchable(false)
            ->width('80px')
            ->align('center');
    }

    /**
     * Cria column de link/URL
     */
    protected function makeLinkColumn(string $field, string $label, ?string $route = null): Column
    {
        $column = Column::make($field, $label)
            ->type('link')
            ->sortable()
            ->searchable();

        if ($route) {
            $column->route($route);
        }

        return $column;
    }

    /**
     * Cria column de número/valor
     */
    protected function makeNumberColumn(string $field, string $label, ?string $format = null): Column
    {
        $column = Column::make($field, $label)
            ->type('number')
            ->sortable()
            ->searchable()
            ->align('right');

        if ($format) {
            $column->format($format);
        }

        return $column;
    }

    /**
     * Cria column de moeda
     */
    protected function makeCurrencyColumn(string $field, string $label): Column
    {
        return Column::make($field, $label)
            ->type('currency')
            ->sortable()
            ->searchable()
            ->align('right')
            ->format('R$ #,##0.00');
    }

    /**
     * Cria column de boolean (sim/não)
     */
    protected function makeBooleanColumn(string $field, string $label): Column
    {
        return Column::make($field, $label)
            ->type('boolean')
            ->sortable()
            ->searchable()
            ->width('80px')
            ->align('center');
    }

    /**
     * Aplica configurações padrão para columns comuns
     */
    protected function getDefaultColumns(): array
    {
        return [
            Column::make('id', 'ID')
                ->type('number')
                ->sortable()
                ->width('80px')
                ->align('center'),

            Column::make('name', 'Nome')
                ->type('text')
                ->sortable()
                ->searchable(),

            $this->makeDateTimeColumn('created_at', 'Criado em'),
            $this->makeDateTimeColumn('updated_at', 'Atualizado em'),
            $this->makeStatusColumn(),
            $this->makeActionsColumn(),
        ];
    }

    /**
     * Ordena columns por prioridade
     */
    protected function sortColumnsByOrder(array $columns): array
    {
        return collect($columns)->sortBy(function (Column $column) {
            return $column->getOrder() ?? 999;
        })->values()->toArray();
    }

    /**
     * Agrupa columns por categoria
     */
    protected function groupColumnsByCategory(array $columns): array
    {
        return collect($columns)->groupBy(function (Column $column) {
            return $column->getCategory() ?? 'default';
        })->toArray();
    }

    /**
     * Aplicar estilos condicionais nas columns
     */
    protected function applyConditionalStyling(array $columns): array
    {
        return array_map(function (Column $column) {
            // Aplicar estilos baseados em condições
            if ($column->getName() === 'status') {
                $column->conditionalStyle([
                    'active' => 'text-green-600 bg-green-100',
                    'inactive' => 'text-red-600 bg-red-100',
                    'pending' => 'text-yellow-600 bg-yellow-100',
                ]);
            }

            return $column;
        }, $columns);
    }

    /**
     * Placeholder para método que deve ser implementado pelo controller
     */
    abstract protected function columns(): array;
} 