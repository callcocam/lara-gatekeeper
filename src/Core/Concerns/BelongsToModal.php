<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Concerns;

use Closure;

trait BelongsToModal
{
    protected ?array $fields = null;

    protected Closure|string|null $modalHeading = null;

    protected Closure|string|null $modalDescription = null;

    protected Closure|string|null $modalConfirmButtonText = "Enviar";

    protected Closure|string|null $modalCancelButtonText = "Cancelar";

    protected Closure|string|null $confirmButtonVariant = 'default';

    protected Closure|bool $modalCloseOnOutsideClick = true;

    public function modal(Closure|string|null $modal): self
    {
        $this->modal = $modal;
        return $this;
    }

    public function getModal(): ?array
    {
        return [
            'fields' => array_map(fn($field) => $field->toArray(), $this->getFields()),
            'modalHeading' => $this->getModalHeading(),
            'modalDescription' => $this->getModalDescription(),
            'confirmButtonText' => $this->getModalConfirmButtonText(),
            'cancelButtonText' => $this->getModalCancelButtonText(),
            'closeOnOutsideClick' => $this->getModalCloseOnOutsideClick(),
            'confirmButtonVariant' => $this->getConfirmButtonVariant(),
        ];
    }

    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function getFields(): ?array
    {
        return $this->evaluate($this->fields);
    }

    public function modalHeading(Closure|string|null $heading): self
    {
        $this->modalHeading = $heading;
        return $this;
    }

    public function getModalHeading(): ?string
    {
        return $this->evaluate($this->modalHeading);
    }

    public function modalDescription(Closure|string|null $description): self
    {
        $this->modalDescription = $description;
        return $this;
    }

    public function getModalDescription(): ?string
    {
        return $this->evaluate($this->modalDescription);
    }

    public function modalConfirmButtonText(Closure|string|null $text): self
    {
        $this->modalConfirmButtonText = $text;
        return $this;
    }

    public function confirmButtonVariant(Closure|string|null $variant): self
    {
        $this->confirmButtonVariant = $variant;
        return $this;
    }

    public function getModalConfirmButtonText(): ?string
    {
        return $this->evaluate($this->modalConfirmButtonText);
    }

    public function modalCancelButtonText(Closure|string|null $text): self
    {
        $this->modalCancelButtonText = $text;
        return $this;
    }

    public function getModalCancelButtonText(): ?string
    {
        return $this->evaluate($this->modalCancelButtonText);
    }

    public function modalCloseOnOutsideClick(Closure|bool $close): self
    {
        $this->modalCloseOnOutsideClick = $close;
        return $this;
    }

    public function getModalCloseOnOutsideClick(): ?bool
    {
        return $this->evaluate($this->modalCloseOnOutsideClick);
    }

    public function getConfirmButtonVariant(): ?string
    {
        return $this->evaluate($this->confirmButtonVariant);
    }
}
