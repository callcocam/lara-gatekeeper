<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Traits;

trait HasImport
{
    protected ?string $import = null;

    protected ?string $importEndpoint = '';

    public function import(string $import): static
    {
        $this->import = $import;
        return $this;
    }

    public function importEndpoint(string $importEndpoint): static
    {
        $this->importEndpoint = $importEndpoint;
        return $this;
    }

    public function getImportEndpoint(): ?string
    {
        return $this->importEndpoint;
    }


    public function getImport(): ?array
    {
        return [
            'import' => $this->import,
            'endpoint' => $this->importEndpoint,
        ];
    }
}
