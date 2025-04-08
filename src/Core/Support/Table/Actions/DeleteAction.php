<?php

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Actions;

class DeleteAction extends Action
{
    public function __construct(?string $name = 'delete', ?string $label = 'Excluir')
    {
        parent::__construct($name, $label);

        $this->variant('destructive');
        $this->icon('Trash');
    }   

    public static function make(?string $name = 'delete', ?string $label = 'Excluir'): static
    {
        return parent::make($name, $label);
    }
} 