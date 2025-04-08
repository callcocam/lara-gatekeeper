<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

trait HasExport
{
    protected ?string $export = null;

    protected ?string $exportEndpoint = null;

    public function export(string $export): static
    {
        $this->export = $export;
        return $this;
    }

    public function exportEndpoint(string $exportEndpoint): static
    {
        $this->exportEndpoint = $exportEndpoint;
        return $this;
    }

    public function getExportEndpoint(): ?string
    {
        return $this->exportEndpoint;
    }

    public function getExport(): ?array
    {
        return [
            'export' => $this->export,
            'endpoint' => $this->exportEndpoint,
        ];
    }
}