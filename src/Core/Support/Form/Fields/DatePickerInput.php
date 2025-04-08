<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class DatePickerInput extends Field
{
    protected string $component = 'DatePickerInput';

    protected ?string $type = 'date';

    protected string $format = 'short';

    protected ?string $locale = 'pt-BR';

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);

        // $this->placeholder(__('Selecione uma data'));
    }


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
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'type' => $this->getType(),
            'format' => $this->getFormat(),
            'locale' => $this->getLocale(),
        ]);
    }
}
