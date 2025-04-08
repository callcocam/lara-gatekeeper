<?php
/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Actions;

class ViewAction extends Action
{
    public function __construct(?string $name = 'show', ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->icon('EyeIcon');
        $this->variant('default');
    } 

    public static function make(?string $name = 'show', ?string $label = 'Ver'): static
    {
        return parent::make($name, $label);
    }
    
}