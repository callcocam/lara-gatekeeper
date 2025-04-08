<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Actions;

class EditAction extends Action
{
    public function __construct(?string $name = 'edit', ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->icon('Pencil');
        $this->variant('default');
    } 

    public static function make(?string $name = 'edit', ?string $label = 'Editar'): static
    {
        return parent::make($name, $label);
    }
    
}