<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Callcocam\LaraGatekeeper\Core\Support\Action;
use Callcocam\LaraGatekeeper\Core\Support\Field;
use Callcocam\LaraGatekeeper\Core\Support\ImportAction;

/**
 * Trait responsável pelo processamento de import/export
 * 
 * Gerencia:
 * - Actions de importação
 * - Actions de exportação
 * - Configurações de import/export
 * - Validações específicas
 */
trait ProcessesImportExport
{
    /**
     * Obtém opções de importação
     * Pode ser sobrescrito por controllers filhos
     */
    protected function getImportOptions(): array
    {
        if (!$this->shouldShowImportOptions()) {
            return [];
        }

        return [
            // ImportAction::make('import')
            //     ->order(5)
            //     ->icon('Upload')
            //     ->color('info')
            //     ->routeNameBase($this->getRouteNameBase())
            //     ->routeSuffix('import')
            //     ->label('Importar')
            //     ->requiresPermissions('import-data')
            //     ->visibleWhen(fn() => $this->canImport()),
        ];
    }

    /**
     * Obtém opções de exportação
     * Pode ser sobrescrito por controllers filhos
     */
    protected function getExportOptions(): array
    {
        if (!$this->shouldShowExportOptions()) {
            return [];
        }

        return [
            // Action::make('export-excel')
            //     ->order(6)
            //     ->icon('Download')
            //     ->color('success')
            //     ->routeNameBase($this->getRouteNameBase())
            //     ->routeSuffix('export')
            //     ->label('Exportar Excel')
            //     ->requiresPermissions('export-data')
            //     ->visibleWhen(fn() => $this->canExport()),

            // Action::make('export-pdf')
            //     ->order(7)
            //     ->icon('FileText')
            //     ->color('danger')
            //     ->routeNameBase($this->getRouteNameBase())
            //     ->routeSuffix('export-pdf')
            //     ->label('Exportar PDF')
            //     ->requiresPermissions('export-data')
            //     ->visibleWhen(fn() => $this->canExport()),
        ];
    }

    /**
     * Verifica se deve mostrar opções de importação
     */
    protected function shouldShowImportOptions(): bool
    {
        return property_exists($this, 'allowImport') ? $this->allowImport : true;
    }

    /**
     * Verifica se deve mostrar opções de exportação
     */
    protected function shouldShowExportOptions(): bool
    {
        return property_exists($this, 'allowExport') ? $this->allowExport : true;
    }

    /**
     * Verifica se usuário pode importar
     */
    protected function canImport(): bool
    {
        return auth()->check() &&
            auth()->user()->can('import-data') &&
            $this->shouldShowImportOptions();
    }

    /**
     * Verifica se usuário pode exportar
     */
    protected function canExport(): bool
    {
        return auth()->check() &&
            auth()->user()->can('export-data') &&
            $this->shouldShowExportOptions();
    }

    /**
     * Cria action de importação customizada
     */
    protected function makeImportAction(string $name, string $label, string $route): ImportAction
    {
        return ImportAction::make($name)
            ->icon('Upload')
            ->type('import')
            ->position('top')
            ->method('POST')
            ->url($route)
            ->label($label)
            ->requiresPermissions('import-data')
            ->visibleWhen(fn() => $this->canImport())
            ->fields([
                Field::make('file', 'Arquivo')
                    ->type('file')
            ]);
    }

    /**
     * Cria action de exportação customizada
     */
    protected function makeExportAction(string $name, string $label, string $route, string $format = 'excel'): Action
    {
        $color = match ($format) {
            'pdf' => 'danger',
            'csv' => 'warning',
            'excel' => 'success',
            default => 'info'
        };

        $icon = match ($format) {
            'pdf' => 'FileText',
            'csv' => 'File',
            'excel' => 'Download',
            default => 'Download'
        };

        return Action::make($name)
            ->icon($icon)
            ->color($color)
            ->routeNameBase($this->getRouteNameBase())
            ->routeSuffix($route)
            ->label($label)
            ->requiresPermissions('export-data')
            ->visibleWhen(fn() => $this->canExport());
    }

    /**
     * Actions de importação específicas
     */

    /**
     * Importação de Excel
     */
    protected function makeImportExcelAction(): Action
    {
        return $this->makeImportAction('import-excel', 'Importar Excel', 'import');
    }

    /**
     * Importação de CSV
     */
    protected function makeImportCsvAction(): Action
    {
        return $this->makeImportAction('import-csv', 'Importar CSV', 'import-csv');
    }

    /**
     * Importação em lote
     */
    protected function makeBulkImportAction(): Action
    {
        return $this->makeImportAction('bulk-import', 'Importação em Lote', 'bulk-import')
            ->requiresPermissions(['import-data', 'bulk-operations']);
    }

    /**
     * Actions de exportação específicas
     */

    /**
     * Exportação filtrada
     */
    protected function makeFilteredExportAction(): Action
    {
        return $this->makeExportAction('export-filtered', 'Exportar Filtrados', 'export-filtered')
            ->visibleWhen(fn() => request()->hasAny(['search', 'filter']));
    }

    /**
     * Exportação completa
     */
    protected function makeFullExportAction(): Action
    {
        return $this->makeExportAction('export-all', 'Exportar Todos', 'export-all')
            ->requiresPermissions(['export-data', 'export-all']);
    }

    /**
     * Exportação de relatório
     */
    protected function makeReportExportAction(): Action
    {
        return $this->makeExportAction('export-report', 'Gerar Relatório', 'export-report', 'pdf')
            ->requiresPermissions(['export-data', 'generate-reports']);
    }

    /**
     * Configurações avançadas de import/export
     */

    /**
     * Define formatos permitidos para importação
     */
    protected function getAllowedImportFormats(): array
    {
        return ['xlsx', 'xls', 'csv'];
    }

    /**
     * Define formatos permitidos para exportação
     */
    protected function getAllowedExportFormats(): array
    {
        return ['xlsx', 'csv', 'pdf'];
    }

    /**
     * Define tamanho máximo de arquivo para importação (em MB)
     */
    protected function getMaxImportFileSize(): int
    {
        return 10; // 10MB
    }

    /**
     * Define configurações de importação
     */
    protected function getImportConfig(): array
    {
        return [
            'max_file_size' => $this->getMaxImportFileSize(),
            'allowed_formats' => $this->getAllowedImportFormats(),
            'chunk_size' => 1000,
            'validate_headers' => true,
            'skip_errors' => false,
        ];
    }

    /**
     * Define configurações de exportação
     */
    protected function getExportConfig(): array
    {
        return [
            'allowed_formats' => $this->getAllowedExportFormats(),
            'chunk_size' => 5000,
            'include_headers' => true,
            'date_format' => 'd/m/Y',
            'encoding' => 'UTF-8',
        ];
    }

    /**
     * Valida se arquivo pode ser importado
     */
    protected function validateImportFile($file): bool
    {
        $config = $this->getImportConfig();

        // Verifica tamanho
        if ($file->getSize() > $config['max_file_size'] * 1024 * 1024) {
            return false;
        }

        // Verifica formato
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $config['allowed_formats'])) {
            return false;
        }

        return true;
    }

    /**
     * Placeholder para método que deve ser implementado pelo controller
     */
    abstract protected function getRouteNameBase(): string;
}
