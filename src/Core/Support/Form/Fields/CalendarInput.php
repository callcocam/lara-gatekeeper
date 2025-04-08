<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Form\Fields;

use Callcocam\LaraGatekeeper\Core\Support\Form\Field;

class CalendarInput extends Field
{
    protected string $component = 'CalendarInput';

    protected string $format = 'Y-m-d';

    protected bool $enableTime = false;

    protected bool $inline = false;

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function enableTime(bool $enableTime = true): static
    {
        $this->enableTime = $enableTime;

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function isEnableTime(): bool
    {
        return $this->enableTime;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'format' => $this->getFormat(),
            'enableTime' => $this->isEnableTime(),
            'inline' => $this->isInline(),
        ]);
    }
}
