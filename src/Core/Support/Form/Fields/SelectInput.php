<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */

 namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

 use Callcocam\LaraGatekeeper\Core\Support\Form\Field;
 use Callcocam\LaraGatekeeper\Core\Support\Concerns;

class SelectInput extends Field
{
    use Concerns\BelongsToOptions;

    protected string $component = 'SelectInput';

    protected ?string $type = 'select';

    protected ?bool $multiple = false;

    protected ?bool $searchable = false;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function getMultiple(): ?bool
    {
        return $this->multiple;
    }

    public function getSearchable(): ?bool
    {
        return $this->searchable;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'multiple' => $this->getMultiple(),
            'searchable' => $this->getSearchable(),
            'options' => $this->getOptions(),
        ]);
    }
}
