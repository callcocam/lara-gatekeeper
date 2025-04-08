<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class TagsInput extends Field
{
    protected string $component = 'TagsInput'; 
    protected ?string $separator = null;
    protected ?string $delimiter = null;
 

    public function separator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    public function delimiter(string $delimiter): static
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [ 
            'separator' => $this->separator,
            'delimiter' => $this->delimiter,
            'value' => data_get($model, $this->getName()),
        ]);
    }
}
