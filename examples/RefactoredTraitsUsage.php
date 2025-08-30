<?php

/**
 * Exemplos de uso dos traits refatorados
 * 
 * Demonstra como usar os traits separados de forma individual
 * ou combinada para diferentes tipos de controllers.
 */

namespace Callcocam\LaraGatekeeper\Examples;

use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesActions;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesFields;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesColumns;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesImportExport;
use Callcocam\LaraGatekeeper\Traits\Controllers\ProcessesFieldsAndColumns;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Support\Column;
use Callcocam\LaraGatekeeper\Core\Support\Action;
use Illuminate\Database\Eloquent\Model;

// ============================================================================
// EXEMPLO 1: Controller usando apenas Actions (API ou simples)
// ============================================================================

/**
 * Controller simples que só precisa de actions
 */
class SimpleApiController
{
    use ProcessesActions;
    
    protected function getRouteNameBase(): string
    {
        return 'api.products';
    }
    
    protected function getImportOptions(): array
    {
        return []; // Sem import para API
    }
    
    protected function getExportOptions(): array
    {
        return []; // Sem export para API
    }
    
    protected function getCustomActions(): array
    {
        return [
            $this->makePublishAction(),
            $this->makeArchiveAction(),
        ];
    }
    
    public function getAvailableActions()
    {
        return $this->getTableActions();
    }
}

// ============================================================================
// EXEMPLO 2: Controller usando apenas Fields (Forms only)
// ============================================================================

/**
 * Controller de formulário que só trabalha com fields
 */
class FormOnlyController
{
    use ProcessesFields;
    
    protected function fields(?Model $model = null): array
    {
        return [
            Field::make('name', 'Nome')
                ->type('text')
                ->required()
                ->autoPolicy('update'), // Usa descoberta automática
                
            Field::make('email', 'E-mail')
                ->type('email')
                ->required()
                ->requiresPermissions('edit-email'),
                
            Field::make('status', 'Status')
                ->type('select')
                ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                ->visibleWhen(fn($item, $user) => $user->hasRole('admin')),
        ];
    }
    
    public function getFormData(?Model $model = null)
    {
        $context = $model ? 'edit' : 'create';
        
                 return [
             'fields' => $this->setupFields($model, $context),
             'initial_values' => $model 
                 ? $this->getInitialValuesForEdit($model, $this->fields($model))
                 : [],
         ];
    }
}

// ============================================================================
// EXEMPLO 3: Controller usando apenas Columns (Tables/Reports)
// ============================================================================

/**
 * Controller de relatório que só trabalha com columns
 */
class ReportController
{
    use ProcessesColumns;
    
    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')
                ->type('number')
                ->sortable()
                ->width('80px'),
                
            Column::make('name', 'Nome')
                ->type('text')
                ->sortable()
                ->searchable()
                ->autoPolicy('view'), // Usa descoberta automática
                
            $this->makeDateTimeColumn('created_at', 'Criado em'),
            $this->makeCurrencyColumn('total_value', 'Valor Total'),
            $this->makeStatusColumn(),
        ];
    }
    
    public function getTableData()
    {
        return [
            'columns' => $this->processTableColumns(),
            'headers' => $this->getTableHeaders(),
        ];
    }
}

// ============================================================================
// EXEMPLO 4: Controller usando Import/Export apenas
// ============================================================================

/**
 * Controller especializado em import/export
 */
class ImportExportController
{
    use ProcessesImportExport;
    
    protected function getRouteNameBase(): string
    {
        return 'admin.data';
    }
    
    protected function getCustomActions(): array
    {
        return [
            $this->makeBulkImportAction(),
            $this->makeFilteredExportAction(),
            $this->makeReportExportAction(),
        ];
    }
    
    public function getImportExportOptions()
    {
        return [
            'import' => $this->getImportOptions(),
            'export' => $this->getExportOptions(),
            'config' => [
                'import' => $this->getImportConfig(),
                'export' => $this->getExportConfig(),
            ],
        ];
    }
}

// ============================================================================
// EXEMPLO 5: Controller CRUD completo usando trait unificado
// ============================================================================

/**
 * Controller CRUD completo usando o trait unificado
 */
class FullCrudController
{
    use ProcessesFieldsAndColumns; // Trait unificado
    
    protected function getRouteNameBase(): string
    {
        return 'admin.products';
    }
    
    protected function fields(?Model $model = null): array
    {
        return [
            Field::make('name', 'Nome')
                ->type('text')
                ->required()
                ->autoPolicy('update'),
                
            Field::make('description', 'Descrição')
                ->type('textarea')
                ->autoPolicy('update'),
                
            Field::make('price', 'Preço')
                ->type('number')
                ->step(0.01)
                ->min(0)
                ->autoPolicy('update')
                ->requiresPermissions('edit-prices'),
                
            Field::make('category_id', 'Categoria')
                ->type('select')
                ->relationship('categories')
                ->autoPolicy('update'),
                
            Field::make('status', 'Status')
                ->type('select')
                ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                ->visibleWhen(fn($item, $user) => $user->can('change-status')),
        ];
    }
    
    protected function columns(): array
    {
        return [
            Column::make('id', 'ID')
                ->type('number')
                ->sortable()
                ->width('80px'),
                
            Column::make('name', 'Nome')
                ->type('text')
                ->sortable()
                ->searchable(),
                
            $this->makeRelationshipColumn('category', 'name', 'Categoria'),
            $this->makeCurrencyColumn('price', 'Preço'),
            $this->makeStatusColumn(),
            $this->makeDateTimeColumn('created_at', 'Criado em'),
            $this->makeActionsColumn(),
        ];
    }
    
    protected function getCustomActions(): array
    {
        return [
            $this->makePublishAction(),
            $this->makeDuplicateAction(),
            $this->makeArchiveAction(),
        ];
    }
    
    // Métodos do controller usando os helpers dos traits
    
    public function index()
    {
        $data = $this->prepareIndexData();
        
        return view('admin.products.index', $data);
    }
    
    public function create()
    {
        $data = $this->prepareCreateFormData();
        
        return view('admin.products.create', $data);
    }
    
    public function edit($product)
    {
        $data = $this->prepareEditFormData($product);
        
        return view('admin.products.edit', $data);
    }
    
    public function show($product)
    {
        $data = $this->prepareShowData($product);
        
        return view('admin.products.show', $data);
    }
}

// ============================================================================
// EXEMPLO 6: Controller customizado combinando traits específicos
// ============================================================================

/**
 * Controller customizado que usa apenas os traits necessários
 */
class CustomProductController
{
    use ProcessesFields,    // Para formulários
        ProcessesColumns,   // Para tabelas
        ProcessesActions {  // Para actions
            ProcessesActions::getTableActions as getBaseTableActions;
        }
    
    protected function getRouteNameBase(): string
    {
        return 'products';
    }
    
    // Implementações obrigatórias dos traits
    protected function getImportOptions(): array { return []; }
    protected function getExportOptions(): array { return []; }
    
    protected function fields(?Model $model = null): array
    {
        return [
            Field::make('name', 'Nome do Produto')
                ->type('text')
                ->required()
                ->autoPolicy('update')
                ->visibleWhen(function($item, $user) {
                    // Lógica customizada de visibilidade
                    return !$item || $item->status !== 'archived';
                }),
                
            Field::make('price', 'Preço')
                ->type('currency')
                ->required()
                ->autoPolicy('update')
                ->requiresPermissions('edit-product-prices'),
        ];
    }
    
    protected function columns(): array
    {
        return [
            $this->addCustomColumn('id', 'ID', 'number')
                ->width('60px')
                ->sortable(),
                
            $this->addCustomColumn('name', 'Nome')
                ->searchable()
                ->sortable(),
                
            $this->makeCurrencyColumn('price', 'Preço'),
            $this->makeStatusColumn(),
            $this->makeActionsColumn(),
        ];
    }
    
    protected function getCustomActions(): array
    {
        return [
            // Action customizada com descoberta automática
            Action::make('quick-edit')
                ->icon('Edit2')
                ->color('info')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('quick-edit')
                ->label('Edição Rápida')
                ->autoPolicy('update')
                ->visibleWhen(fn($item, $user) => $user->hasRole('admin')),
                
            // Action customizada com permissões específicas
            Action::make('bulk-update')
                ->icon('Package')
                ->color('warning')
                ->routeNameBase($this->getRouteNameBase())
                ->routeSuffix('bulk-update')
                ->label('Atualização em Lote')
                ->requiresPermissions(['bulk-edit', 'edit-products'])
                ->hiddenWhenShow(),
        ];
    }
    
    // Override do método de actions para adicionar lógica customizada
    protected function getTableActions(): array
    {
        $baseActions = $this->getBaseTableActions();
        
        // Adiciona ações condicionais baseadas no contexto
        if (request()->has('archived')) {
            $baseActions[] = $this->makeRestoreAction();
        }
        
        return $baseActions;
    }
}

// ============================================================================
// RESUMO DAS VANTAGENS DA ARQUITETURA REFATORADA:
// ============================================================================

/*
🚀 BENEFÍCIOS DA SEPARAÇÃO:

1. **RESPONSABILIDADE ÚNICA:**
   - ProcessesActions: Apenas lógica de actions
   - ProcessesFields: Apenas lógica de formulários
   - ProcessesColumns: Apenas lógica de tabelas
   - ProcessesImportExport: Apenas lógica de import/export

2. **FLEXIBILIDADE TOTAL:**
   - Use apenas os traits que precisa
   - Combine da forma que fizer sentido
   - Override métodos específicos sem afetar outros

3. **REUTILIZAÇÃO INTELIGENTE:**
   - Cada trait pode ser usado independentemente
   - Métodos helpers específicos para cada responsabilidade
   - Descoberta automática de policies integrada

4. **MANUTENIBILIDADE:**
   - Código mais organizado e fácil de encontrar
   - Cada trait tem responsabilidade clara
   - Fácil de testar individualmente

5. **COMPATIBILIDADE 100%:**
   - ProcessesFieldsAndColumns mantido como interface unificada
   - Todo código existente continua funcionando
   - Migração gradual possível

6. **EXTENSIBILIDADE:**
   - Fácil adicionar novos traits especializados
   - Methods de conveniência em cada trait
   - Sistema de descoberta automática integrado

7. **PERFORMANCE:**
   - Carrega apenas o que precisa
   - Menos overhead para controllers simples
   - Cache de permissões mantido
*/ 