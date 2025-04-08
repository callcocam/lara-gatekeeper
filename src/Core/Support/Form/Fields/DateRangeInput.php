<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class DateRangeInput extends Field
{
    protected string $component = 'DateRangeInput';
    
    protected ?string $type = 'date';
    
    protected string $format = 'short';

    protected ?string $locale = 'pt-BR'; 
    
    protected ?string $startPlaceholder = 'Start date';
    
    protected ?string $endPlaceholder = 'End date';
    
    public function locale(string $locale): static
    {
        $this->locale = $locale;
        
        return $this;
    }
     
    public function format(string $format): static
    {
        $this->format = $format;
        
        return $this;
    }
    
    public function startPlaceholder(?string $placeholder): static
    {
        $this->startPlaceholder = $placeholder;
        
        return $this;
    }
    
    public function endPlaceholder(?string $placeholder): static
    {
        $this->endPlaceholder = $placeholder;
        
        return $this;
    }
    
    public function getFormat(): string
    {
        return $this->format;
    }
    
    public function getStartPlaceholder(): ?string
    {
        return $this->startPlaceholder;
    }
    
    public function getEndPlaceholder(): ?string
    {
        return $this->endPlaceholder;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'format' => $this->getFormat(),
            'startPlaceholder' => $this->getStartPlaceholder(),
            'endPlaceholder' => $this->getEndPlaceholder(),
        ]);
    }
}
