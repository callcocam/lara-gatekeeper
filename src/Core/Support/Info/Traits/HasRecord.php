<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaraGatekeeper\Core\Support\Info\Traits;

trait HasRecord
{
    protected $record;

    protected array $withLoad = [];

    protected array $appendLoad = [];

    public function record($record): static
    {
        $this->record = $record;
        return $this;
    }   

    public function getRecord()
    {
        
        $this->record->load($this->getWithLoad());

        $this->record->append($this->getAppendLoad());

        return $this->record;
    }

    public function withLoad(array $with): static
    {
        $this->withLoad = $with;
        return $this;
    }

    public function getWithLoad(): array
    {
        return $this->withLoad;
    }

    public function appendLoad(array $with): static
    {
        $this->appendLoad = $with;
        return $this;
    }

    public function getAppendLoad(): array
    {
        return $this->appendLoad;
    }
}