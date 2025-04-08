<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 */
namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class ColorPickerInput extends Field
{
    protected string $component = 'ColorPickerInput';
    protected ?string $type = 'color';
    protected ?string $format = 'hex';

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'format' => $this->format,
            'value' => $model ? data_get($model, $this->getName()) : $this->default,
        ]);
    }
}
