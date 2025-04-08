<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class FileInput extends Field
{
    protected string $component = 'FileInput';
    protected ?string $type = 'file';
    protected bool $multiple = false;
    protected ?string $accept = null;
    protected ?int $maxSize = null;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function accept(string $accept): static
    {
        $this->accept = $accept;
        return $this;
    }

    public function maxSize(int $size): static
    {
        $this->maxSize = $size;
        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'multiple' => $this->multiple,
            'accept' => $this->accept,
            'maxSize' => $this->maxSize,
            'value' => $model ? data_get($model, $this->getName()) : null,
        ]);
    }
}
