<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class CheckboxInput extends Field
{
    protected string $component = 'CheckboxInput';

    protected ?string $type = 'checkbox';

    protected bool $checked = false;


    public function checked(bool $checked = true): static
    {
        $this->checked = $checked;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'label' => $this->getLabel(),
            'checked' => $model ? (bool) data_get($model, $this->getName(), $this->isChecked()) : $this->isChecked(),
        ]);
    }
}
