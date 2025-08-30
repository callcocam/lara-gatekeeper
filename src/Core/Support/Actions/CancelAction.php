<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Actions;

use Callcocam\LaraGatekeeper\Core\Support\Action as SupportAction;

class CancelAction extends SupportAction
{
    protected string $component = 'Link';

    public function __construct(string $name, ?string $header = null)
    {
        parent::__construct($name, $header);
        $this
            ->order(0)
            ->position('footer')
            ->icon('X')
            ->hiddenWhenIndex()
            ->variant('secondary')
            ->label('Cancelar');
    }
}
