<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 * 
 * */

namespace Callcocam\LaraGatekeeper\Core\Support\Form;

use Callcocam\LaraGatekeeper\Core\Support\Concerns;

class Repeater extends Sections
{
    use Concerns\BelongsToDialog;

    protected ?string $addText = "Adicionar";
    protected ?string $addIcon = 'PlusIcon';
    protected ?string $removeText = null;
    protected ?string $removeIcon = null;
    protected ?string $removeAllText = null;
    protected ?string $removeAllIcon = null;

    protected ?bool $requiredConfirmation = null;

    protected ?bool $sortable = true;
    protected ?string $sortableIcon = null;



    public function __construct(?string $label = null)
    {
        parent::__construct($label);
        $this->component('SCRepeater');
    }

    public static function make(?string $label = null): static
    {
        return new static($label);
    }
    public function addText(?string $addText): static
    {
        $this->addText = $addText;
        return $this;
    }
    public function addIcon(?string $addIcon): static
    {
        $this->addIcon = $addIcon;
        return $this;
    }
    public function removeText(?string $removeText): static
    {
        $this->removeText = $removeText;
        return $this;
    }
    public function removeIcon(?string $removeIcon): static
    {
        $this->removeIcon = $removeIcon;
        return $this;
    }
    public function removeAllText(?string $removeAllText): static
    {
        $this->removeAllText = $removeAllText;
        return $this;
    }

    public function removeAllIcon(?string $removeAllIcon): static
    {
        $this->removeAllIcon = $removeAllIcon;
        return $this;
    }
    public function requiredConfirmation(?bool $requiredConfirmation): static
    {
        $this->requiredConfirmation = $requiredConfirmation;
        return $this;
    }
    public function getAddText(): ?string
    {
        return $this->addText;
    }

    public function getAddIcon(): ?string
    {
        return $this->addIcon;
    }

    public function getRemoveText(): ?string
    {
        return $this->removeText;
    }

    public function getRemoveIcon(): ?string
    {
        return $this->removeIcon;
    }

    public function getRemoveAllText(): ?string
    {
        return $this->removeAllText;
    }

    public function getRemoveAllIcon(): ?string
    {
        return $this->removeAllIcon;
    }

    public function getRequiredConfirmation(): ?bool
    {
        return $this->requiredConfirmation;
    }

    public function sortable(?bool $sortable): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function sortableIcon(?string $sortableIcon): static
    {
        $this->sortableIcon = $sortableIcon;
        return $this;
    }

    public function getSortable(): ?bool
    {
        return $this->sortable;
    }

    public function getSortableIcon(): ?string
    {
        return $this->sortableIcon;
    }

    public function toArray($model = null): array
    {
        return array_merge(parent::toArray($model), [
            'addText' => $this->evaluate($this->getAddText(), [
                'record' => $model
            ]),
            'addIcon' => $this->evaluate($this->getAddIcon(), [
                'record' => $model
            ]),
            'removeText' => $this->evaluate($this->getRemoveText(), [
                'record' => $model
            ]),
            'removeIcon' => $this->evaluate($this->getRemoveIcon(), [
                'record' => $model
            ]),
            'removeAllText' => $this->evaluate($this->getRemoveAllText(), [
                'record' => $model
            ]),
            'removeAllIcon' => $this->evaluate($this->getRemoveAllIcon(), [
                'record' => $model
            ]),
            'requiredConfirmation' => $this->evaluate($this->getRequiredConfirmation(), [
                'record' => $model
            ]),
            'sortable' => $this->evaluate($this->getSortable(), [
                'record' => $model
            ]),
            'sortableIcon' => $this->evaluate($this->getSortableIcon(), [
                'record' => $model
            ]),
            'dialogTitle' => $this->evaluate($this->getDialogTitle(), [
                'record' => $model
            ]),
            'dialogDescription' => $this->evaluate($this->getDialogDescription(), [
                'record' => $model
            ]),
            'dialogSize' => $this->evaluate($this->getDialogSize(), [
                'record' => $model
            ]),
            'dialogOpen' => $this->evaluate($this->getDialogOpen(), [
                'record' => $model
            ]),
        ]);
    }
}
