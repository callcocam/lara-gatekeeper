<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

trait HasCombobox
{
    protected ?string $apiEndpoint = null;
    public function combobox(): self
    {
        $this->type = 'combobox';
        return $this;
    } 

    public function apiEndpoint(string $apiEndpoint, int $levels = 4): self
    {
        $this->inputProps['apiEndpoint'] = $apiEndpoint; 
        $this->inputProps['apiUrl'] = $apiEndpoint;
        $this->inputProps['levels'] = $levels;
        return $this;
    }
 
    
}