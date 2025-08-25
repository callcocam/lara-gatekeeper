<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

trait ProvidesExtraData
{
    /**
     * Retorna dados extras para a página index
     */
    protected function getExtraDataForIndex(): array
    {
        return [];
    }

    /**
     * Retorna dados extras para a página create
     */
    protected function getExtraDataForCreate(): array
    {
        return [];
    }

    /**
     * Retorna dados extras para a página edit
     */
    protected function getExtraDataForEdit(): array
    {
        return [];
    }

    /**
     * Retorna opções de importação
     */
    protected function getImportOptions(): array
    {
        if (method_exists($this, 'getImport')) {
            return $this->getImport();
        }
        return [];
    }

    /**
     * Retorna opções de exportação
     */
    protected function getExportOptions(): array
    {
        if (method_exists($this, 'getExport')) {
            return $this->getExport();
        }
        return [];
    }
}
